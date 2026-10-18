# Launch notes for 3D Plate Builder

Internal notes for the owner. Don't publish this fil## 1. What's filled in, and what's left

Everything below is filled in across the site from `placeholders.json`. To change any of it, edit that file and run `python3 tools/fill-placeholders.py --final` again. That only works on a copy that still has the placeholders, so keep the original zip.

**Filled in (change anything you like):**
- **Name and domain:** 3D Plate Builder, 3dplatebuilder.com.
- **Email:** hello@3dplatebuilder.com (all demo requests and support). Create this mailbox, and `noreply@3dplatebuilder.com` for the form to send from.
- **Demo form:** posts to https://3dplatebuilder.com/contact.php. Replies "within one working day"; support Monday to Friday, 9am to 5pm.
- **Website + builder:** Starter £89/mo + £495, Pro £179/mo + £995, Complete £299/mo + £1,995. Outright £5,950, then £995 a year with 1 hour of changes a month.
- **Builder only:** Starter £49/mo + £295, Pro £99/mo + £395, Complete £149/mo + £695. Outright £2,450, then £495 a year.
- **Add-ons:** custom style £395, plate logo £125, design work £55 an hour, migration from £495 (included in Complete).
- **Plan limits:** Starter 6 pages / 30 products; Pro 15 pages / 100 products; Complete 30 pages, unlimited products, 4 launch articles, 2 hours of changes a month.
- **Terms on the pricing page:** 12-month minimum, then one month's notice. Moving down a plan takes effect at the end of the minimum term. Backups every day. Hosting on "our managed servers". If a customer leaves, they get a full copy of their site, products and orders, and help moving their domain.
- **Set-up timelines:** demo within 2 working days, build 1–2 weeks, design 3–5 working days, payments, documents and go-live 1 working day each, testing 2–3 working days. Total 2–4 weeks.

**Still needed before launch:**
- **Company details for the footer on every page:** [COMPANY LEGAL NAME], [COMPANY NUMBER], [REGISTERED ADDRESS]. UK company websites must show these. If you trade as a sole trader, the line needs rewording instead.
- **The legal pages (privacy, terms, cookies):** still marked as templates for your solicitor. Their remaining [BRACKETS] are listed when you run the fill script.
- **Proof stats and a testimonial:** taken off the home page until you have real figures. The home page says the builder is taking real orders at a UK plate shop, without naming it. Send me order counts, phone share and a quote when you have them, and I'll add them back.
- **Check that these match how you work:** the daily backups and the leaving terms are promises on the pricing page.

e.

## 2. Coming soon (not promised in any plan)

The site has no phone number or WhatsApp anywhere: demos are set up and emailed as a private link, and support is by email.

Shown on the Features page and marked "Coming soon" in the pricing table:

- **Delivery choice cards and the fitting charge at checkout, as a packaged feature.** In the original shop these came from WPCode snippets that aren't in the product. The builder's own fitting add-on is built.
- **3D gel and 4D on motorbike, trailer and import sizes.** These sizes are printed only (`plate-sizes.js`).
- **More cars** in the "on a car" view. Only the built-in, code-drawn sports saloon exists today.
- **Support tickets from each shop's admin.** You're building this; until then the site says support is by email.
- **An account dashboard** for plan, invoices and updates, and for managing more than one shop.
- **Automatic updates and licence keys** for sites bought outright and builder-only licences. Until then, you install builder updates on builder-only sites yourself, as part of the plan.

## 3. Licence checks before launch

- **Charles Wright font data** (`builder/assets/fonts/charles-wright-bold.json` and `charles-wright-singapore.json`). These were made from font files licensed to the original plate shop, not to 3D Plate Builder. 3D Plate Builder needs its own licence (from K-Type or whoever owns them) that covers web/app embedding **and** using them across other businesses' websites. The outlines are served to every visitor's browser. The dimensioned plate drawing on the home page is also drawn from these outlines.
- **3D car models.** None are in the kit; the car view uses the built-in sports saloon, drawn in code. Clear the licence for any bought GLB models (`car-model.js`, `CARS`) before you add them, including the right to use them on other companies' sites.
- **Sheeting brand on the plate backs.** Since builder 1.10 the backs are plain unless a shop sets `backing` in its shop config. Only print a sheeting maker's brand with their permission.
- **Three.js** is MIT-licensed: fine to resell; keep its copyright notice (it's in the CDN files).
- **Union Flag / UK and EV badge artwork** in `plate-badges.js`. Worth a quick check that the artwork is yours to use.
- **Archivo** (this site's typeface) is under the SIL Open Font License. No action needed.

- **"iPhone" in the copy.** The phone preview is drawn in CSS (no Apple artwork), and the captions name the iPhone 18 Pro Max only to say which screen size it shows. That's usually fine as a factual reference, but ask your solicitor, or change it to "a 6.9-inch phone screen" if you'd rather not name it.

## 4. White-labelling the builder (done in 1.10)

Everything shop-specific in the builder now comes from each shop's config (`pbb_shop`, set in WooCommerce → Plate Builder → Shop details, or by provisioning): supplier and postcode, logo, brand colours, fitting wording, car badge, plate-back print, product and page IDs, category presets and menus. A fresh install is neutral.

The demo on this site uses the same config keys, so the old run-time patches in `demo.js` are gone. After changing the builder, run `tools/sync-demo-builder.sh` to copy it into the demo.

Still to check: test the documents step end to end on the staging shop before selling Complete.

## 5. Suggested prices, and why

There are two ways to buy, at the same three levels:

- **Website + builder:** we design, build, host and look after a plate shop. Each plan adds more of the builder *and* more website.
- **Builder only:** we install the builder on the plate maker's own WordPress/WooCommerce site. They keep their own hosting and WordPress updates; we keep the builder updated.

I couldn't find published prices for comparable plate-builder platforms, so these are worked out from what the product saves a plate maker and what the website work costs you, rather than from competitors. Test them on your first few demos.

**Website + builder**

| | Suggested | What the website adds | Why |
|---|---|---|---|
| Starter | £79–£99 a month, £395–£595 set-up | Ready-made plate-shop design in their colours, up to 6 pages, up to 30 products, hosting, Analytics | Two or three extra pairs a month pays for it. A template keeps your set-up time low. |
| Pro | £149–£199 a month, £795–£1,195 set-up | Custom home page, up to 15 pages with plate-type landing pages, up to 100 products with photos laid out, search basics, Google reviews, domain email | Roughly double Starter for the showroom features plus real design and search work. |
| Complete | £279–£349 a month, £1,495–£2,495 set-up | Fully custom design, up to 30 pages with local town pages, unlimited products, migration included, 4 launch articles, 2 hours of changes a month, monthly report | Includes ongoing hours and the most set-up work (custom design, migration, Stripe, documents, test orders). It's the plan most busy shops should pick. |
| Outright | £4,950–£7,950, then £795–£1,195 a year (with 1 hour of changes a month) | Everything in Complete | About 18–24 months of Complete. |

**Builder only**

| | Suggested | Why |
|---|---|---|
| Starter | £39–£49 a month, £195–£295 set-up | No hosting or website work on your side, so roughly half the website price. Set-up is a site check, install, prices and a test order. |
| Pro | £79–£99 a month, £295–£495 set-up | Product-page placement takes more fitting into their theme. |
| Complete | £129–£159 a month, £495–£795 set-up | Checkout in the builder and document checks have to be tested against their own checkout, which takes the longest. |
| Outright licence | £1,950–£2,950, then £395–£595 a year for updates and support | About 15–20 months of builder-only Complete. |

**Add-ons:** custom style £295–£495, plate logo £95–£150, design work £45–£65 an hour, migration £295–£995 (included in Complete).

**Plan limits** (pages, products, articles, hours) are placeholders too, so you can tune what each tier includes: [STARTER PAGES], [STARTER PRODUCTS], [PRO PAGES], [PRO PRODUCTS], [COMPLETE PAGES], [COMPLETE LAUNCH ARTICLES], [COMPLETE MONTHLY HOURS], [OUTRIGHT MONTHLY HOURS].

**Things to decide for Builder only:**
- Which WordPress/WooCommerce set-ups you'll accept. The README notes page-builder checkouts (Elementor/ShopBuilder) needed extra work.
- Whether builder-only Complete includes the delivery and fitting cards. These aren't in the product yet, so the site doesn't promise them.
- How you'll stop a builder-only licence being copied to other sites. There's no licence-key system in the kit yet, so this is in "Coming soon" and the terms limit it to one domain.

Set-up fees also filter out tyre-kickers and cover your time before the first monthly payment. A 12-month minimum term with one month's notice after that is normal for managed sites, and protects the set-up work.
