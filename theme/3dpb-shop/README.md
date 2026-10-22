# 3dPlateBuilder Shop theme

The theme every customer shop runs: a child of **Twenty Twenty-Five** (free, comes with WordPress).
Needs the 3D Plate Builder plugin and WooCommerce.

## What changes per shop, and where it comes from

The customer's logo, main colour, dark colour and heading font come from their 3dPlateBuilder
account: the plugin saves them in `pbb_shop` on Publish, and `inc/brand.php` feeds them into
theme.json, so blocks, the editor and WooCommerce all use them. Text on the main colour is picked
automatically (dark or white). Photos and wording are edited in WordPress. Layout, sections, body font
(Geist) and spacing are fixed.

Fonts are stored in the theme (`assets/fonts`, all OFL-licensed), so nothing loads from Google.

## Setting up a new shop

1. Install WordPress, WooCommerce, WooCommerce Stripe Gateway and the 3D Plate Builder plugin.
2. Copy this folder to `wp-content/themes/3dpb-shop` and activate **3dPlateBuilder Shop**.
3. **Appearance → Shop set-up**: choose the plan and click **Set up the shop**
   (or `wp 3dpb setup --plan=starter|pro|complete`). It builds:
   - every plan: Home, Help and contact (`/contact-us/`), Documents we need (`/valid-documents/`),
     Terms, Privacy; basket and checkout titles; UK-only store in pounds;
   - Pro and Complete: the Pro home page, Design your plates, a page for each of the seven styles
     (`/4d-gel-3mm-number-plates/` etc.) and the Number plates menu.
   Existing pages are kept unless "Replace existing pages" is ticked.
4. Add the licence key under WooCommerce → Plate Builder and publish from the portal.

## Files

- `theme.json`: colours, fonts, sizes, buttons. Slugs `accent`, `accent-ink` and `dark` are set per shop.
- `parts/`: header (brand, menu, basket, notice bar), footer (supplier line), checkout header.
- `templates/`: `front-page` and `page-landing` (no title), `page` (title band), `page-checkout`.
- `patterns/`: Starter one-page shop, Pro home, style page, help page, and the shared sections.
- Shortcodes: `[pbb_brand]`, `[pbb_info key="name|email|address|supplier|postcode"]`, `[pbb_from style="…"]`.
  A style switched off in the account hides its card and links (`pbb-style-<id>` class).
