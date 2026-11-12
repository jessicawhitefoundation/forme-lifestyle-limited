<?php

namespace Tests\Feature\Store;

use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Mail\NewOrderAdminMail;
use App\Mail\NewOrderCustomerMail;
use App\Mail\OrderShippedMail;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StoreSetting;
use App\Models\User;
use App\Services\OrderMailService;
use App\Services\OrderPaymentService;
use App\Services\RoutelyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    private const COURIER_FEE = 2500;

    private const SECRET = 'sk_test_for_tests_only';

    /** @var list<string> */
    private array $initializedReferences = [];

    /**
     * What the fake Routely answers for overseas quotes: express rates, or
     * 'down' for an error. Null (the default) gives no overseas rates, so the
     * backup fee is used.
     *
     * @var list<array<string, mixed>>|string|null
     */
    private array|string|null $routelyOverseas = null;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.paystack.secret_key' => self::SECRET,
            'services.routely.api_key' => 'rty_test_for_tests_only',
        ]);

        // A guest's bag follows their session, so keep one across requests.
        $this->withCredentials();
        $this->useBrowserSession('a');

        $this->initializedReferences = [];

        $this->routelyOverseas = null;

        Http::fake(function (HttpRequest $request) {
            if (str_contains($request->url(), '/shipments/fee') && isset($request['to_country'])) {
                if ($this->routelyOverseas === 'down') {
                    return Http::response(['success' => false, 'message' => 'Service unavailable'], 500);
                }

                return Http::response(['success' => true, 'data' => ['rates' => ['express' => $this->routelyOverseas ?? []]]]);
            }

            if (str_contains($request->url(), '/shipments/fee')) {
                return Http::response(['success' => true, 'data' => ['rates' => [[
                    'selling_price' => self::COURIER_FEE,
                    'carrier_name' => 'Test Courier',
                    'delivery_time' => '1 day',
                ]]]]);
            }

            if (str_contains($request->url(), '/shipments/create')) {
                return Http::response(['success' => true, 'data' => ['id' => 55, 'tracking_number' => 'TRACK-55']]);
            }

            if (str_contains($request->url(), 'transaction/initialize')) {
                $this->initializedReferences[] = $request['reference'];

                return Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/test']]);
            }

            return Http::response(['status' => false], 500);
        });
    }

    private function useBrowserSession(string $letter): void
    {
        $this->withCookie(config('session.cookie'), str_repeat($letter, 40));
    }

    private function makeVariant(float $price = 50000, int $stock = 5, string $categorySlug = 'women'): ProductVariant
    {
        $product = Product::create([
            'category_id' => Category::where('slug', $categorySlug)->value('id'),
            'name' => 'Test Set '.uniqid(),
            'slug' => 'test-set-'.uniqid(),
            'base_price' => $price,
            'is_active' => true,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'M',
            'color' => 'Black',
            'stock_quantity' => $stock,
            'price_modifier' => 0,
            'is_active' => true,
        ]);
    }

    private function addToCart(ProductVariant $variant, int $quantity = 1): void
    {
        $this->postJson('/cart', ['variant_id' => $variant->id, 'quantity' => $quantity])->assertOk();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function details(array $overrides = []): array
    {
        return $overrides + [
            'customer_name' => 'Ada Test',
            'customer_email' => 'ada@example.com',
            'customer_phone' => '+2348012345678',
            'shipping_address' => '1 Test Street',
            'shipping_city' => 'Lekki',
            'shipping_state' => 'Lagos',
        ];
    }

    private function initiate(array $overrides = [])
    {
        return $this->post('/checkout/initiate', $this->details($overrides), ['X-Inertia' => 'true']);
    }

    /**
     * @return array<string, mixed>
     */
    private function chargeData(Order $order, array $overrides = []): array
    {
        return $overrides + [
            'status' => 'success',
            'reference' => $order->order_number.'.abc12345',
            'amount' => (int) round((float) $order->total_amount * 100),
            'currency' => 'NGN',
            'authorization' => ['reusable' => true, 'authorization_code' => 'AUTH_test', 'last4' => '4081', 'brand' => 'visa'],
        ];
    }

    private function sendWebhook(array $data, ?string $signature = null)
    {
        $payload = json_encode(['event' => 'charge.success', 'data' => $data]);

        return $this->call('POST', '/webhooks/paystack', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => $signature ?? hash_hmac('sha512', $payload, self::SECRET),
        ], $payload);
    }

    public function test_a_guest_can_check_out_without_an_account(): void
    {
        $this->addToCart($this->makeVariant());

        $this->get('/checkout')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Store/Checkout'));

        $response = $this->initiate();

        $response->assertStatus(409);
        $this->assertSame('https://checkout.paystack.com/test', $response->headers->get('X-Inertia-Location'));

        $order = Order::sole();
        $this->assertNull($order->user_id);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame('ada@example.com', $order->customer_email);
    }

    public function test_a_phone_number_is_required(): void
    {
        $this->addToCart($this->makeVariant());

        $this->initiate(['customer_phone' => ''])->assertSessionHasErrors('customer_phone');
        $this->assertSame(0, Order::count());
    }

    public function test_retrying_payment_keeps_the_order_and_uses_a_new_reference(): void
    {
        $this->addToCart($this->makeVariant());

        $this->initiate()->assertStatus(409);
        Cache::flush();
        $this->initiate()->assertStatus(409);

        $this->assertSame(1, Order::count());
        $this->assertCount(2, $this->initializedReferences);
        $this->assertNotSame($this->initializedReferences[0], $this->initializedReferences[1]);
        $this->assertSame(Order::sole()->id, Order::findByPaymentReference($this->initializedReferences[1])->id);
    }

    public function test_changing_the_address_creates_a_new_order_instead_of_shipping_to_the_old_one(): void
    {
        $this->addToCart($this->makeVariant());

        $this->initiate()->assertStatus(409);
        Cache::flush();
        $this->initiate(['shipping_address' => '2 New Road'])->assertStatus(409);

        $this->assertSame(2, Order::count());
        $this->assertSame('2 New Road', Order::latest('id')->first()->shipping_address);
    }

    public function test_the_webhook_accepts_paystack_without_a_csrf_token_and_marks_the_order_paid(): void
    {
        Mail::fake();
        $variant = $this->makeVariant(50000, 5);
        $this->addToCart($variant, 2);
        $this->initiate();
        $order = Order::sole();

        $this->sendWebhook($this->chargeData($order))->assertOk();

        $order->refresh();
        $this->assertTrue($order->isPaid());
        $this->assertSame('processing', $order->status);
        $this->assertSame(3, $variant->fresh()->stock_quantity);
        $this->assertSame(0, Cart::find($order->cart_id)->items()->count());
        Mail::assertSent(NewOrderCustomerMail::class, 1);
        Mail::assertSent(NewOrderAdminMail::class, 1);
    }

    public function test_order_emails_come_from_the_orders_mailbox(): void
    {
        Mail::fake();
        config([
            'mail.orders_from.address' => 'orders@shopforme.net',
            'mail.admin_address' => 'hello@shopforme.net',
            'store.email' => 'hello@shopforme.net',
        ]);
        $this->addToCart($this->makeVariant());
        $this->initiate();
        $order = Order::sole();

        $this->sendWebhook($this->chargeData($order))->assertOk();

        // The customer can reply to the store's contact address.
        Mail::assertSent(NewOrderCustomerMail::class, fn ($mail) => $mail->hasTo($order->customer_email)
            && $mail->hasFrom('orders@shopforme.net')
            && $mail->hasReplyTo('hello@shopforme.net'));

        // The store's alert: replying writes to the customer.
        Mail::assertSent(NewOrderAdminMail::class, fn ($mail) => $mail->hasTo('hello@shopforme.net')
            && $mail->hasFrom('orders@shopforme.net')
            && $mail->hasReplyTo($order->customer_email));
    }

    public function test_a_repeated_payment_notice_does_not_deduct_stock_or_notify_twice(): void
    {
        Mail::fake();
        $variant = $this->makeVariant(50000, 5);
        $this->addToCart($variant);
        $this->initiate();
        $order = Order::sole();
        $payments = app(OrderPaymentService::class);

        // The customer's return from Paystack and the webhook both arrive.
        $this->assertSame(OrderPaymentService::PAID, $payments->markPaid($order, $this->chargeData($order)));
        $this->assertSame(OrderPaymentService::ALREADY_PAID, $payments->markPaid($order, $this->chargeData($order)));

        $this->assertSame(4, $variant->fresh()->stock_quantity);
    }

    public function test_the_webhook_rejects_a_forged_signature(): void
    {
        $this->addToCart($this->makeVariant());
        $this->initiate();
        $order = Order::sole();

        $this->sendWebhook($this->chargeData($order), 'forged')->assertForbidden();

        $this->assertFalse($order->fresh()->isPaid());
    }

    public function test_an_underpayment_does_not_mark_the_order_paid(): void
    {
        $this->addToCart($this->makeVariant());
        $this->initiate();
        $order = Order::sole();

        $this->sendWebhook($this->chargeData($order, ['amount' => 100]))->assertOk();

        $this->assertFalse($order->fresh()->isPaid());
    }

    public function test_an_unpaid_order_is_not_shown_as_confirmed_but_a_paid_one_is(): void
    {
        Mail::fake();
        $this->addToCart($this->makeVariant());
        $this->initiate();
        $order = Order::sole();

        $this->get($order->confirmationUrl())
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('order.payment_status', 'unpaid'));

        $this->sendWebhook($this->chargeData($order));

        $this->get($order->confirmationUrl())
            ->assertInertia(fn (Assert $page) => $page->where('order.payment_status', 'paid'));
    }

    public function test_order_details_need_a_signed_link_or_the_matching_session(): void
    {
        $this->addToCart($this->makeVariant());
        $this->initiate();
        $order = Order::sole();

        $this->get("/order/{$order->order_number}/confirmation")->assertOk();

        // Someone else's browser: no session link to the order, and no signature.
        session()->flush();
        $this->useBrowserSession('b');
        $this->get("/order/{$order->order_number}/confirmation")->assertForbidden();
        $this->get($order->confirmationUrl())->assertOk();
    }

    public function test_a_guest_can_find_an_order_only_with_the_matching_email(): void
    {
        $this->addToCart($this->makeVariant());
        $this->initiate();
        $order = Order::sole();

        $this->post('/track-order', ['order_number' => $order->order_number, 'email' => 'someone@else.com'])
            ->assertSessionHasErrors('order_number');

        $this->post('/track-order', ['order_number' => $order->order_number, 'email' => 'ada@example.com'])
            ->assertRedirect($order->confirmationUrl());
    }

    public function test_lagos_orders_at_the_threshold_ship_free_and_the_courier_cost_is_kept(): void
    {
        $this->addToCart($this->makeVariant(200000));

        $this->initiate()->assertStatus(409);

        $order = Order::sole();
        $this->assertTrue($order->free_shipping);
        $this->assertSame(0.0, (float) $order->shipping_fee);
        $this->assertSame(200000.0, (float) $order->total_amount);
        $this->assertSame((float) self::COURIER_FEE, (float) $order->routely_fee);
    }

    public function test_lagos_orders_below_the_threshold_pay_delivery(): void
    {
        $this->addToCart($this->makeVariant(199999));

        $this->initiate()->assertStatus(409);

        $order = Order::sole();
        $this->assertFalse($order->free_shipping);
        $this->assertSame(199999.0 + self::COURIER_FEE, (float) $order->total_amount);
    }

    public function test_orders_outside_lagos_always_pay_delivery(): void
    {
        $this->addToCart($this->makeVariant(500000));

        $this->initiate(['shipping_state' => 'Cross River', 'shipping_city' => 'Calabar'])->assertStatus(409);

        $order = Order::sole();
        $this->assertFalse($order->free_shipping);
        $this->assertSame((float) self::COURIER_FEE, (float) $order->shipping_fee);
    }

    public function test_the_estimator_matches_the_free_delivery_rule(): void
    {
        $this->addToCart($this->makeVariant(150000));

        $this->postJson('/checkout/shipping-rate', ['state' => 'Lagos', 'city' => 'Ikeja'])
            ->assertOk()
            ->assertJson(['free_shipping' => false, 'shipping_fee' => self::COURIER_FEE, 'free_shipping_remaining' => 50000]);

        $this->postJson('/checkout/shipping-rate', ['state' => 'Kano'])
            ->assertOk()
            ->assertJson(['free_shipping' => false, 'free_shipping_remaining' => 0]);
    }

    public function test_vat_is_off_by_default(): void
    {
        $this->addToCart($this->makeVariant());
        $this->initiate();

        $this->assertSame(0.0, (float) Order::sole()->vat_amount);
    }

    public function test_vat_can_be_added_on_top_of_the_price(): void
    {
        StoreSetting::current()->update(['vat_enabled' => true, 'vat_rate' => 7.5, 'vat_inclusive' => false]);
        $this->addToCart($this->makeVariant(100000));

        $this->initiate();

        $order = Order::sole();
        $this->assertSame(7500.0, (float) $order->vat_amount);
        $this->assertSame(100000.0 + 7500 + self::COURIER_FEE, (float) $order->total_amount);
    }

    public function test_vat_included_in_the_price_does_not_change_the_total(): void
    {
        StoreSetting::current()->update(['vat_enabled' => true, 'vat_rate' => 7.5, 'vat_inclusive' => true]);
        $this->addToCart($this->makeVariant(107500));

        $this->initiate();

        $order = Order::sole();
        $this->assertSame(7500.0, (float) $order->vat_amount);
        $this->assertSame(107500.0 + self::COURIER_FEE, (float) $order->total_amount);
    }

    public function test_the_saved_card_token_never_reaches_the_browser(): void
    {
        $user = User::factory()->create(['paystack_auth_code' => 'AUTH_secret', 'card_last_four' => '4081', 'card_brand' => 'visa']);

        $this->actingAs($user);
        $this->addToCart($this->makeVariant());

        $this->get('/checkout')
            ->assertInertia(fn (Assert $page) => $page
                ->missing('auth.user.paystack_auth_code')
                ->missing('user.paystack_auth_code')
                ->where('savedCard.last_four', '4081'));

        $this->assertStringNotContainsString('AUTH_secret', $this->get('/account')->getContent());
    }

    public function test_a_card_is_saved_only_when_the_customer_opts_in(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->addToCart($this->makeVariant());
        $this->initiate();
        $this->sendWebhook($this->chargeData(Order::sole()));
        $this->assertNull($user->fresh()->paystack_auth_code);

        $this->addToCart($this->makeVariant());
        $this->post('/checkout/initiate', $this->details(['save_card' => true]), ['X-Inertia' => 'true']);
        $this->sendWebhook($this->chargeData(Order::latest('id')->first()));
        $this->assertSame('AUTH_test', $user->fresh()->paystack_auth_code);
    }

    public function test_a_guest_cannot_save_a_card(): void
    {
        $this->addToCart($this->makeVariant());

        $this->post('/checkout/initiate', $this->details(['save_card' => true]), ['X-Inertia' => 'true']);

        $this->assertFalse(Order::sole()->save_card);
    }

    public function test_a_customer_can_remove_their_saved_card(): void
    {
        $user = User::factory()->create(['paystack_auth_code' => 'AUTH_secret', 'card_last_four' => '4081', 'card_brand' => 'visa']);

        $this->actingAs($user)->delete('/account/saved-card')->assertRedirect();

        $this->assertNull($user->fresh()->paystack_auth_code);
        $this->assertNull($user->fresh()->card_last_four);
    }

    public function test_booking_a_courier_works_and_uses_the_real_cost_on_free_delivery_orders(): void
    {
        $this->addToCart($this->makeVariant(200000));
        $this->initiate();
        $order = Order::sole();

        $result = app(RoutelyService::class)->createShipment($order);

        $this->assertTrue($result['success']);
        $this->assertSame('TRACK-55', $order->fresh()->routely_tracking_number);
        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), '/shipments/create')
            && (float) $request['total_amount'] === (float) self::COURIER_FEE);
    }

    public function test_the_shipping_email_is_sent_once(): void
    {
        Mail::fake();
        $this->addToCart($this->makeVariant());
        $this->initiate();

        OrderMailService::sendOrderShippedNotification(Order::sole());
        app()->terminate();

        Mail::assertSent(OrderShippedMail::class, 1);
    }

    /** Delivers to the UK for ₦45,000 at ₦2,000 to the pound. */
    private function enableUk(bool $enabled = true): void
    {
        StoreSetting::current();
        StoreSetting::findOrFail(1)->update([
            'international_rates' => [
                'GB' => ['enabled' => $enabled, 'fee' => 45000, 'rate' => 2000],
                'US' => ['enabled' => false, 'fee' => 60000, 'rate' => 1500],
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function ukAddress(array $overrides = []): array
    {
        return $overrides + [
            'shipping_country' => 'GB',
            'shipping_address' => '221B Baker Street',
            'shipping_city' => 'London',
            'shipping_state' => '',
            'shipping_postcode' => 'nw1 6xe',
            'customer_phone' => '+447700900123',
        ];
    }

    public function test_overseas_delivery_is_off_until_the_store_switches_it_on(): void
    {
        $this->addToCart($this->makeVariant());

        $this->get('/checkout')->assertInertia(fn (Assert $page) => $page->has('countries', 1)->where('countries.0.code', 'NG'));
        $this->initiate($this->ukAddress())->assertSessionHasErrors('shipping_country');

        $this->enableUk(enabled: false);
        $this->initiate($this->ukAddress())->assertSessionHasErrors('shipping_country');

        $this->assertSame(0, Order::count());
    }

    public function test_a_uk_quote_falls_back_to_the_backup_fee_when_routely_has_no_price(): void
    {
        $this->enableUk();
        $this->addToCart($this->makeVariant(50000));

        $this->postJson('/checkout/shipping-rate', ['country' => 'GB'])
            ->assertOk()
            ->assertJson([
                'is_international' => true,
                'country' => 'United Kingdom',
                'carrier' => 'DHL Express',
                'shipping_fee' => 45000,
                'free_shipping' => false,
                'total' => 95000,
                'approx' => ['currency' => 'GBP', 'symbol' => '£', 'total' => 47.5],
            ]);

        // A country the store hasn't switched on can't be quoted.
        $this->postJson('/checkout/shipping-rate', ['country' => 'US'])->assertUnprocessable();

        Http::assertNotSent(fn (HttpRequest $request) => str_contains($request->url(), '/shipments/fee') && ! isset($request['to_country']));
    }

    public function test_a_uk_customer_can_check_out_and_pays_by_card_in_naira(): void
    {
        $this->enableUk();
        $this->addToCart($this->makeVariant(50000));

        $this->initiate($this->ukAddress())->assertStatus(409);

        $order = Order::sole();
        $this->assertSame('United Kingdom', $order->shipping_country);
        $this->assertSame('NW1 6XE', $order->shipping_postcode);
        $this->assertSame('DHL Express via Routely', $order->shipping_carrier);
        $this->assertSame(45000.0, (float) $order->shipping_fee);
        $this->assertSame(95000.0, (float) $order->total_amount);
        $this->assertSame(45000.0, (float) $order->routely_fee);
        $this->assertTrue($order->isInternational());
        $this->assertSame('221B Baker Street, London, NW1 6XE, United Kingdom', $order->fullAddress());

        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), 'transaction/initialize')
            && $request['amount'] === 9500000
            && $request['currency'] === 'NGN'
            && $request['channels'] === ['card']);
        Http::assertNotSent(fn (HttpRequest $request) => str_contains($request->url(), '/shipments/fee') && ! isset($request['to_country']));

        // Paying again with the same details reuses the order.
        $this->initiate($this->ukAddress())->assertStatus(409);
        $this->assertSame(1, Order::count());

        // The checkout page offers the country (it opens on Nigeria, priced by Routely).
        $this->get('/checkout')->assertInertia(fn (Assert $page) => $page->has('countries', 2)->where('countries.1.name', 'United Kingdom'));
    }

    public function test_overseas_orders_never_carry_vat(): void
    {
        $this->enableUk();
        StoreSetting::findOrFail(1)->update(['vat_enabled' => true, 'vat_rate' => 7.5, 'vat_inclusive' => false]);
        $this->addToCart($this->makeVariant(50000));

        // Nigeria: 7.5% is added on top.
        $this->postJson('/checkout/shipping-rate', ['state' => 'Lagos'])
            ->assertJson(['vat_amount' => 3750, 'total' => 50000 + self::COURIER_FEE + 3750]);

        // UK: an export, zero-rated.
        $this->postJson('/checkout/shipping-rate', ['country' => 'GB'])
            ->assertJson(['vat_amount' => 0, 'total' => 95000]);

        $this->initiate($this->ukAddress())->assertStatus(409);
        $this->assertSame(0.0, (float) Order::sole()->vat_amount);
    }

    public function test_the_us_and_canada_work_once_switched_on(): void
    {
        StoreSetting::current();
        StoreSetting::findOrFail(1)->update([
            'international_rates' => [
                'GB' => ['enabled' => true, 'fee' => 45000, 'rate' => 2100],
                'US' => ['enabled' => true, 'fee' => 60000, 'rate' => 1600],
                'CA' => ['enabled' => true, 'fee' => 65000, 'rate' => 1150],
            ],
        ]);
        $this->addToCart($this->makeVariant(50000));

        $this->get('/checkout')->assertInertia(fn (Assert $page) => $page
            ->has('countries', 4)
            ->where('countries.2.name', 'United States')
            ->where('countries.2.postcode_label', 'ZIP code')
            ->where('countries.3.name', 'Canada')
            ->where('countries.3.region_label', 'Province'));

        $this->postJson('/checkout/shipping-rate', ['country' => 'US'])
            ->assertJson(['shipping_fee' => 60000, 'approx' => ['currency' => 'USD', 'symbol' => '$', 'total' => 68.75]]);
        $this->postJson('/checkout/shipping-rate', ['country' => 'CA'])
            ->assertJson(['shipping_fee' => 65000, 'approx' => ['currency' => 'CAD', 'symbol' => 'C$']]);

        // The US and Canada need a state or province; the UK's county is optional.
        $this->initiate($this->ukAddress(['shipping_country' => 'US', 'shipping_postcode' => '10001']))->assertSessionHasErrors('shipping_state');
        $this->initiate($this->ukAddress(['shipping_country' => 'CA', 'shipping_state' => 'Ontario', 'shipping_city' => 'Toronto', 'shipping_postcode' => 'M5V 2T6']))->assertStatus(409);
        $this->assertSame('Canada', Order::sole()->shipping_country);
        $this->assertSame(115000.0, (float) Order::sole()->total_amount);
    }

    public function test_overseas_checkout_needs_a_postcode(): void
    {
        $this->enableUk();
        $this->addToCart($this->makeVariant());

        $this->initiate($this->ukAddress(['shipping_postcode' => '']))->assertSessionHasErrors('shipping_postcode');
        $this->initiate($this->ukAddress(['shipping_postcode' => '<script>']))->assertSessionHasErrors('shipping_postcode');
        $this->assertSame(0, Order::count());
    }

    public function test_nigerian_orders_are_unchanged_when_overseas_delivery_is_on(): void
    {
        $this->enableUk();
        $this->addToCart($this->makeVariant(50000));

        $this->initiate()->assertStatus(409);

        $order = Order::sole();
        $this->assertSame('Nigeria', $order->shipping_country);
        $this->assertFalse($order->isInternational());
        $this->assertSame((float) self::COURIER_FEE, (float) $order->shipping_fee);
        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), 'transaction/initialize')
            && in_array('bank_transfer', $request['channels'], true));
    }

    public function test_marking_an_overseas_order_shipped_emails_the_dhl_tracking_number(): void
    {
        Mail::fake();
        $this->enableUk();
        $this->addToCart($this->makeVariant());
        $this->initiate($this->ukAddress());
        $order = Order::sole();
        $order->update(['payment_status' => 'paid', 'status' => 'processing']);

        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Livewire::test(ListOrders::class)
            ->assertTableActionHidden('dispatch_routely', $order)
            ->callTableAction('mark_shipped', $order, data: ['shipping_carrier' => 'DHL Express', 'tracking_number' => ' 1234567890 '])
            ->assertHasNoTableActionErrors();
        app()->terminate();

        $order->refresh();
        $this->assertSame('shipped', $order->status);
        $this->assertSame('1234567890', $order->tracking_number);
        Mail::assertSent(OrderShippedMail::class, function (OrderShippedMail $mail) {
            $html = $mail->render();

            return str_contains($html, '1234567890')
                && str_contains($html, 'tracking-id=1234567890')
                && str_contains($html, 'United Kingdom');
        });
    }

    public function test_overseas_delivery_is_priced_live_by_routely(): void
    {
        $this->enableUk();
        $this->routelyOverseas = [
            ['carrier_name' => 'Economy - UPS Express', 'selling_price' => 81766.10, 'delivery_time' => '4 to 6 working days'],
            ['carrier_name' => 'Premium - DHL Express', 'selling_price' => 95983.40, 'delivery_time' => '2 to 4 working days'],
        ];
        $this->addToCart($this->makeVariant(50000), 2);

        // DHL Express is preferred, even though UPS is cheaper.
        $this->postJson('/checkout/shipping-rate', ['country' => 'GB'])
            ->assertOk()
            ->assertJson(['carrier' => 'DHL Express', 'delivery_time' => '2 to 4 working days', 'shipping_fee' => 95984, 'total' => 195984]);

        // Two items at 0.7 kg; before the postcode is typed, a sample one stands in.
        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), '/shipments/fee')
            && $request['to_country'] === 'United Kingdom'
            && $request['from_country'] === 'Nigeria'
            && (float) $request['weight'] === 1.4
            && $request['to_postal_code'] === 'SW1A 1AA');

        $this->initiate($this->ukAddress())->assertStatus(409);
        $order = Order::sole();
        $this->assertSame(95984.0, (float) $order->shipping_fee);
        $this->assertSame(95984.0, (float) $order->routely_fee);
        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), 'transaction/initialize')
            && $request['amount'] === 19598400);
    }

    public function test_the_weight_per_item_can_be_changed_in_store_settings(): void
    {
        $this->enableUk();
        StoreSetting::findOrFail(1)->update(['international_item_weight' => 1.5]);
        $this->routelyOverseas = [['carrier_name' => 'Premium - DHL Express', 'selling_price' => 98785, 'delivery_time' => '2 to 4 working days']];
        $this->addToCart($this->makeVariant());

        $this->postJson('/checkout/shipping-rate', ['country' => 'GB'])->assertJson(['shipping_fee' => 98785]);
        Http::assertSent(fn (HttpRequest $request) => isset($request['to_country']) && (float) $request['weight'] === 1.5);
    }

    public function test_without_routely_or_a_backup_fee_overseas_checkout_waits(): void
    {
        StoreSetting::current();
        StoreSetting::findOrFail(1)->update(['international_rates' => ['GB' => ['enabled' => true, 'fee' => null, 'rate' => 2000]]]);
        $this->routelyOverseas = 'down';
        $this->addToCart($this->makeVariant());

        $this->postJson('/checkout/shipping-rate', ['country' => 'GB'])
            ->assertStatus(503)
            ->assertJson(['success' => false, 'message' => "We can't get a delivery price to United Kingdom right now. Please try again in a few minutes."]);

        $this->initiate($this->ukAddress())->assertSessionHasErrors('checkout');
        $this->assertSame(0, Order::count());
    }

    public function test_the_last_piece_is_held_for_the_shopper_who_is_paying(): void
    {
        $variant = $this->makeVariant(50000, 1);

        // Shopper A goes to pay for the last one.
        $this->addToCart($variant);
        $this->initiate()->assertStatus(409);

        // Shopper B, in another browser, can still bag it but can't pay for it.
        $this->useBrowserSession('b');
        $this->addToCart($variant);
        $this->initiate(['customer_email' => 'bola@example.com'])
            ->assertSessionHasErrors(['cart' => "Someone else is paying for the last '".$variant->product->name." (M)' right now. If they don't finish, it will be available again in a few minutes."]);
        $this->assertSame(1, Order::count());

        // Shopper A can go back to Paystack again without being blocked by their own hold.
        $this->useBrowserSession('a');
        $this->initiate()->assertStatus(409);

        // A doesn't pay; once the hold lapses, B can buy it.
        $this->travel(config('store.stock_hold_minutes') + 1)->minutes();
        $this->useBrowserSession('b');
        $this->initiate(['customer_email' => 'bola@example.com'])->assertStatus(409);
        $this->assertSame(2, Order::count());
    }

    public function test_a_hold_only_sets_aside_what_was_bagged(): void
    {
        $variant = $this->makeVariant(50000, 3);

        $this->addToCart($variant, 2);
        $this->initiate()->assertStatus(409);

        $this->useBrowserSession('b');
        $this->addToCart($variant, 2);
        $this->initiate(['customer_email' => 'bola@example.com'])->assertSessionHasErrors('cart');

        // One is still free.
        $this->patchJson('/cart/'.CartItem::latest('id')->value('id'), ['quantity' => 1])->assertOk();
        $this->initiate(['customer_email' => 'bola@example.com'])->assertStatus(409);
    }

    public function test_paying_or_cancelling_releases_the_hold(): void
    {
        $variant = $this->makeVariant(50000, 2);
        $this->addToCart($variant);
        $this->initiate();
        $order = Order::sole();

        // Paid: the stock itself goes down instead.
        app(OrderPaymentService::class)->markPaid($order, $this->chargeData($order));
        $this->assertSame(1, $variant->fresh()->stock_quantity);

        $this->useBrowserSession('b');
        $this->addToCart($variant);
        $this->initiate(['customer_email' => 'bola@example.com'])->assertStatus(409);
    }

    public function test_viewing_pages_does_not_create_a_cart_for_every_visitor(): void
    {
        $this->getJson('/cart/count')->assertOk()->assertJson(['count' => 0]);

        $this->assertSame(0, Cart::count());
    }

    public function test_inactive_products_cannot_be_added_to_the_bag(): void
    {
        $variant = $this->makeVariant();
        $variant->product->update(['is_active' => false]);

        $this->postJson('/cart', ['variant_id' => $variant->id, 'quantity' => 1])->assertUnprocessable();
    }

    public function test_signing_in_from_the_bag_returns_to_checkout(): void
    {
        $user = User::factory()->create();

        $this->get('/login?redirect=/checkout')->assertOk();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/checkout');
    }

    public function test_the_sign_in_redirect_cannot_send_someone_to_another_site(): void
    {
        $user = User::factory()->create();

        $this->get('/login?redirect=//evil.example.com')->assertOk();

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertStringNotContainsString('evil.example.com', (string) $response->headers->get('Location'));
    }
}
