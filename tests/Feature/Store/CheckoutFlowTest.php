<?php

namespace Tests\Feature\Store;

use App\Mail\NewOrderAdminMail;
use App\Mail\NewOrderCustomerMail;
use App\Mail\OrderShippedMail;
use App\Models\Cart;
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
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    private const COURIER_FEE = 2500;

    private const SECRET = 'sk_test_for_tests_only';

    /** @var list<string> */
    private array $initializedReferences = [];

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

        Http::fake(function (HttpRequest $request) {
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
