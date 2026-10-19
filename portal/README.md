# 3dPlateBuilder.com portal

The customer area and admin for 3dPlateBuilder.com, at **app.3dplatebuilder.com**. It's built with Laravel 13 and needs PHP 8.3 or newer.

**For plate makers:**
- Sign up, then pay the set-up fee and first month through Stripe Checkout.
- Work through a "what to do next" checklist: company, contact and supplier details, logo, colours and fonts, RNPS document.
- Tick products and set prices, and ask for add-ons.
- See invoices and open Stripe's billing page.
- Raise and reply to support tickets.

**For you (admin):**
- Monthly recurring income, paying shops, set-up fees, overdue payments and churn.
- Every customer, with their stage, links, details, brand files, products and licence key.
- A "build details" JSON download for each shop.
- Document approvals.
- A ticket inbox.
- Payments, including adding add-ons to their next invoice.
- Publishing plugin updates.

Homee Ltd isn't VAT registered yet, so nothing adds VAT.

## How payment works

1. Signing up makes the account, then sends them to Stripe Checkout. Checkout charges the plan's monthly price plus the one-off set-up price.
2. Stripe calls `/stripe/webhook`. When `checkout.session.completed` arrives:
   - the shop is marked paid and moves to Onboarding;
   - a licence key is made;
   - you and they get an email.
3. Invoice events keep a copy of every invoice, so the dashboard can show what's paid and what's overdue. Subscription events keep the next payment date. A cancelled subscription marks the shop Cancelled.

## Plugin updates

The builder plugin calls `GET /updates/check?licence=KEY&version=x.y.z`.
- If the shop has a current subscription, it gets the newest version you've published under **Admin › Plugin updates**, with a download link that works for 30 minutes.
- If the licence isn't active, it gets a 403.

The plugin side of this is the next step.

## Setting it up on Plesk (one time)

Before any of this, check with Harsh, because it needs a subdomain, DNS, SSL and a database.

1. **Subdomain.** Create `app.3dplatebuilder.com`.
   - Set PHP to 8.3 or newer.
   - Set the document root to `/portal/public` inside the Git deploy folder.
   - Issue a Let's Encrypt certificate.
2. **Database.** Create a MySQL database and user for the portal.
3. **Git.** Add the repo `WebologyMarketing/3dplatebuilder`, branch `main`. Deploy it manually, like the live site. For Deploy actions, use `sh portal/deploy.sh`. It:
   - installs packages;
   - makes the app key the first time;
   - migrates the database;
   - caches config;
   - makes the admin login if it's missing.
4. **.env.** Copy `portal/.env.example` to `portal/.env` on the server only, then fill in:
   - the database details;
   - `MAIL_PASSWORD`, for the `noreply@3dplatebuilder.com` mailbox;
   - the Stripe keys (see below).
5. **Your login.** The first deploy emails hello@3dplatebuilder.com a link to choose your admin password. Nobody types a password on the server. If the link expires, use "Forgotten your password?" on the sign-in page.
6. **Storage.** Make sure `portal/storage` and `portal/bootstrap/cache` are writable. Uploads (logos, fonts, RNPS documents, plugin zips) live in `portal/storage/app/private` and are never public.

### Stripe

Use the **3DPlateBuilder.com** account only.

1. **Restricted key.** Under Developers › API keys, create a restricted key with:
   - Checkout Sessions: Write
   - Customer portal: Write
   - Invoice items: Write
   - Customers: Write
   - Subscriptions: Read
   - Invoices: Read

   Put it in `.env` as `STRIPE_SECRET`.
2. **Webhook endpoint.** Under Developers › Webhooks, add `https://app.3dplatebuilder.com/stripe/webhook` with these events:
   - `checkout.session.completed`
   - `customer.subscription.created`, `customer.subscription.updated`, `customer.subscription.deleted`
   - `invoice.created`, `invoice.finalized`, `invoice.updated`, `invoice.paid`, `invoice.payment_failed`, `invoice.voided`, `invoice.marked_uncollectible`

   Put its signing secret in `.env` as `STRIPE_WEBHOOK_SECRET`.
3. **Going live.** Swap the sandbox price ids and keys for the live ones.

Never commit `.env`, keys or customer files.

## Working on it locally

```
cd portal
composer install
cp .env.example .env    # then set DB_CONNECTION=sqlite and APP_ENV=local
php artisan key:generate
php artisan migrate
php artisan db:seed --class=DemoSeeder   # made-up shops; admin@demo.test / demo-password-123
php artisan serve
php artisan test
```

`DemoSeeder` refuses to run in production.
