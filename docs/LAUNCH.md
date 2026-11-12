# FORMÉ launch checklist

The code is ready for a Nigeria launch. These steps need accounts, a server or decisions, so they can't be done in code. Work through them in order.

## 1. Accounts (all in Jessica's name)

- [x] Domain: `shopforme.net` (Namecheap). Confirm it's in Jessica's Namecheap account with auto-renew on.
- [ ] Hosting: Namecheap Stellar Plus, bought in the same account as the domain. Choose the UK or EU datacenter (closer to Nigeria than US), and decline the extras offered at checkout.
- [ ] Paystack: the account must be in Jessica's business name and activated for live payments. Keep the live keys out of chat and email; they go straight into `.env` on the server.
- [ ] Routely: log in and generate a new API key.
- [ ] An email sending service for automatic order emails. The project is set up for Brevo. Don't send these through the hosting mailbox.

Replace every key found in the old `.env`. The previous developer had access to all of them.

## 2. Build on your own computer

Shared hosting usually has no Node, so build before uploading:

```bash
composer install --no-dev --optimize-autoloader
```

```bash
npm ci
```

```bash
npm run build
```

Do not upload:

- `public/hot` (it makes the live site look for your computer's dev server, which breaks the styling and scripts)
- `node_modules`
- `public/images/Debut Collection` (244 MB of original photos; the resized copies in `database/seeders/data/photos` are what the site uses)
- `.env`
- any `.sql` file
- `storage/logs/*.log`

## 3. Server setup

On Namecheap's cPanel first:

- **Nameservers:** in the Namecheap domain list, set `shopforme.net` to "Namecheap Web Hosting DNS" so DNS records are managed in cPanel's Zone Editor.
- **PHP:** "Select PHP Version": choose **8.4** (the packages need 8.4.1 or newer; 8.3 shows a 500 error), and tick `intl`, `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `zip` and `bcmath`.
- **SSH:** switch it on under "Manage Shell", then use cPanel's Terminal for the commands below.
- **Database:** create a database and user under "MySQL Databases" and give the user all privileges. Namecheap adds the cPanel username as a prefix to both names.
- **SSL:** check under "SSL Status" that `shopforme.net` and `www.shopforme.net` have a certificate before testing payments.

1. Upload the project to a folder outside `public_html` (for example `~/forme`), then point the web root at its `public` folder. On a new account `public_html` holds only Namecheap's placeholder page; check that, then replace it with a link: `rm -rf ~/public_html && ln -s ~/forme/public ~/public_html`. The project folder itself must not be publicly reachable. Afterwards, check that `https://shopforme.net/.env` does not open.
2. Copy `.env.example` to `.env` and fill it in. It must include:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://shopforme.net`
   - `LOG_LEVEL=warning`
   - `SESSION_SECURE_COOKIE=true`
   - live `PAYSTACK_*` keys
   - new `ROUTELY_*` and `MAIL_*` details
   - `STORE_EMAIL` and the WhatsApp details
3. Run:

```bash
php artisan key:generate
```

```bash
php artisan migrate --force
```

```bash
php artisan storage:link
```

```bash
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

4. Add a cron job that runs every 5 minutes (Namecheap shared hosting doesn't allow anything more frequent). It cancels abandoned unpaid orders and clears old guest carts:

```
*/5 * * * * cd /path/to/site && php artisan schedule:run >> /dev/null 2>&1
```

Don't run the seeders on the live site. They create sample products and an admin login with a known password. Create the real admin instead, with a strong password:

```bash
php artisan tinker
```

```php
App\Models\User::create(['name' => 'Jessica', 'email' => 'REAL_EMAIL', 'password' => 'STRONG_PASSWORD', 'role' => 'admin']);
```

## 4. Paystack dashboard

- [ ] Webhook URL: `https://shopforme.net/webhooks/paystack`
- [ ] Callback URL: `https://shopforme.net/checkout/callback`
- [x] Switch on international payments (done 12 November; needed for UK / US / Canada orders, see below).

Paystack has one live webhook URL per account. If the old forme.com.ng site uses the same account, changing it stops the old site confirming payments, so close the old checkout (or redirect forme.com.ng to shopforme.net) on the same day.

## 4b. Email

- [ ] Create the store mailbox in cPanel (the code's default is `hello@shopforme.net`; set `STORE_EMAIL`, `MAIL_FROM_ADDRESS`, `ADMIN_NOTIFICATION_EMAIL` and `ROUTELY_SENDER_EMAIL` to whichever address Jessica picks).
- [ ] Brevo: create the account in Jessica's name, authenticate `shopforme.net`, and add the DNS records it gives you in cPanel's Zone Editor. Without them, order emails land in spam.

## 5. Content

- [ ] Load the real catalogue after `storage:link`: `php artisan db:seed --class=LiveCatalogueSeeder --force`. It adds the 26 products from the old forme.com.ng database (prices, stock, categories), with names and colours aligned to Jessica's price list and launch books and the 133 Debut Collection photos, already resized for the web and linked to colours where the file name showed one. It also adds 6 homepage banners and the 4 "Shop by Category" photos, picked from the same photos (`database/seeders/data/hero-slides.json` and `category-photos.json`). Every product starts in New Arrivals, because Jessica counts the whole launch collection as new; untick them in Admin > Products as newer pieces arrive. All 26 products go live. The Nobi Set starts with 10 of each colour, a placeholder set on 11 November until Jessica sends real counts. It adds no users or orders, and running it twice adds nothing twice.
- [ ] Go through `docs/PRODUCT-REVIEW.md` with Jessica: no product has sizes yet, plus colour links and missing photos to confirm.
- [ ] Have Privacy and Terms reviewed. The wording on `/privacy`, `/terms` and `/faq` is a draft based on the existing policy page, and it isn't legal advice.
- [ ] Ask the accountant about VAT. It is off by default; the switch is in Admin > Store Settings.

## 6. Test on the live site before announcing

Use real small payments and refund them afterwards.

- [ ] Guest order in Lagos under ₦200,000: delivery is charged.
- [ ] Guest order in Lagos at ₦200,000 or more: delivery is free, and the order is flagged in Admin > Orders.
- [ ] Order to another state at ₦200,000 or more: delivery is charged.
- [ ] Pay, then close the tab before returning to the site: the order still becomes paid. This proves the webhook works.
- [ ] Customer and admin emails arrive, and aren't in spam.
- [ ] "Forgot password?" sends a working reset email.
- [ ] Save a card and reuse it on a second order, then remove it from the account page.
- [ ] Book one real delivery with **Dispatch Routely**, and confirm the Routely wallet has funds. The booking has only been tested against a simulated Routely. Check the tracking number and the shipping email.

## Daily operations

- **Free-delivery orders:** check the address is really in Lagos before dispatch. Customers choose their own state, so the site can't verify it.
- **Critical log entries:** `storage/logs/laravel.log` records `CRITICAL` entries when a payment doesn't match its order, when an item oversells, or when email isn't configured.
- **Stock is held while a customer pays:** once someone goes to Paystack, their items are set aside for 20 minutes (`STOCK_HOLD_MINUTES`), so two people can't both buy the last piece. A payment that arrives after the hold lapses still goes through; if that oversells, it is logged as `CRITICAL`.

## International delivery (UK, US, Canada)

Live, and off until a country is switched on. Routely prices each overseas order at checkout (DHL Express through Routely, 2–4 working days), by country and parcel weight; the city doesn't change the price. Customers pay that price by card in naira, rounded up to the whole naira; checkout shows a rough £ / $ / C$ total, says import duties may be due on delivery, and adds no VAT (exports are zero-rated). Routely's quotes on 13 November for one item: UK about ₦96,000, US and Canada about ₦114,000.

- [x] Paystack: international payments switched on (12 November). Turn on American Express too if wanted.
- [ ] Admin > Store Settings > International Delivery: switch on each country. Optional: a backup fee (used only if Routely can't give a price; without one, checkout asks the customer to try again) and the exchange rate (₦ per £ / $ / C$) for the rough total.
- [ ] Weigh a typical packed set and enter it as **Typical weight of one item** (default 0.7 kg). Products have no weights, so parcels are estimated as this × the number of items, and Routely prices by weight.
- [ ] For each overseas order: book it in the Routely dashboard (the order shows what the customer paid for delivery), then use **Mark as shipped** in Admin > Orders with Routely's tracking number. The customer gets it by email.
- [ ] Later: request Routely's API documents at routely.co/api, so "Dispatch Routely" can book overseas parcels too. Quotes already work with the store's key; booking overseas through the API is not built.

## Not in this release

- **Wishlist.**
- **Search engine setup:** page descriptions, social previews and a sitemap.
