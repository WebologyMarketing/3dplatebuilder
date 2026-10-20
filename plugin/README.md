# 3D Plate Builder (WordPress plugin)

Real-time 3D/4D number plate customiser for WordPress + WooCommerce.

**Status: Steps 1-5 built, plus DVLA document checks at checkout.**

## Install

1. Zip the plugin folder (the folder is `3d-plate-builder`) and upload it in **Plugins → Add New → Upload Plugin**, or copy it into `wp-content/plugins/`.
2. Activate **3D Plate Builder** (WooCommerce must be active).
3. Go to **WooCommerce → Plate Builder** and set your prices. **The defaults are placeholders.** Opening this screen also creates the hidden "Custom Number Plate" product that builder orders are added as.
4. In **WooCommerce → Plate Builder → Shop details**, set the supplier name and postcode exactly as registered with the DVLA (BS AU 145e prints them in every plate's footer), plus the footer logo, brand colours, fitting wording and car badge. Until the name and postcode are set, the admin shows a warning and plates print the site name and WooCommerce store postcode. See **Shop config** below.

5. Add the shortcode to any page: `[plate_builder]` (the older `[plate_builder]` still works). Options: `side="rear"`, `registration="AB12CDE"`, `style="acrylic-4d-3"`, `accent="blue"`, `size="motorbike"`, `badge="uk-gel"`, `border="black"`.
   A `?reg=AB12CDE` URL parameter pre-fills the builder, so a "search your reg" box or an ad can link straight in.

Requires WordPress 6.5+ for native ES module and import map support.

### Page builders (Elementor, Divi, etc.)

If the builder shows only a spinner, the page builder is hiding the shortcode from `post_content`, so the assets never load. Force them on for that page:

```php
add_filter( 'pbb_should_enqueue', fn ( $on ) => $on || is_page( 'build-your-plate' ) );
```

## Shop config

Everything that makes the builder one shop's lives in one option, `pbb_shop` (`includes/shop.php`). A fresh install has neutral defaults: no supplier branding, no product or page mapping, plain plate backs and no car badge.

| Key | What it does |
|---|---|
| `supplier`, `postcode`, `logo`, `standard` | The plate footer. |
| `accent`, `accent_ink`, `dark` | Brand colours (hex) for buttons, the text on them, and dark panels. Used by the builder, the checkout, the promo blocks and the documents notice. |
| `fitting_note`, `fitting_checkout_note` | The in-store fitting wording in the builder and at checkout. |
| `car_badge` | Image URL for the badge on the car in the "on a car" view. Empty = no badge. |
| `backing` | The sheeting print on the back of the plate: `{ "title", "mark", "tag", "subtitle", "maker" }`. Empty = plain sheeting. Only use a sheeting brand with the maker's permission. |
| `products` | Product ID → the preset the builder starts on (`style`, `size`, `badge`, `match`). |
| `landing_pages`, `feature_front_page` | Page ID → preset, for pages that feature the builder. The front page is included unless `feature_front_page` is false. |
| `category_presets` | Product category slug → preset, for the category promo band. |
| `menu_slugs` | Nav menus that get a "3D Plate Builder" item. |

Shop owners edit the everyday keys on the settings screen; site admins also get the whole config as JSON there. Provisioning sets it in one go:

```sh
wp option update pbb_shop "$(cat shop.json)" --format=json
```

The older filters (`pbb_supplier_details`, `pbb_product_presets`, `pbb_landing_pages`, `pbb_featured_pages`, `pbb_category_presets`, `pbb_menu_slugs`) still run on top of the config. `pbb_documents_email_notice` (default true) turns off the documents box in order emails, for shops that add their own. `pbb_shop_editable` (default true) locks the Shop details form, for hosted shops awaiting verification.

## Managed from the 3dPlateBuilder portal

Plate makers run their own builder from their account at app.3dplatebuilder.com: which styles, sizes,
badges, borders and extras are on sale, every price, their own add-ons (ids `c_xxxxxx`), and (Pro and up)
products with images. They click **Publish**; this plugin pulls the published copy (`includes/portal-sync.php`):

- `GET /api/v1/site` with the `X-Licence-Key` header, when the portal nudges `POST /wp-json/pbb/v1/sync`
  (HMAC-SHA256 of `time.version`, keyed with the licence key), every hour, and on **Save and sync now**.
- Writes `pbb_pricing_v2`, `pbb_offer` (what's on sale), `pbb_custom_accessories` (add-on names, kept for
  past orders), the supplier, postcode, colours and logo in `pbb_shop`, and WooCommerce products. Plate
  products become builder presets; products taken off are set to draft, never deleted.
- Printed, Standard and "None" are always offered. A badge or border step with nothing on sale is left out.

Enter the licence key under WooCommerce → Plate Builder. Without one, everything works as before.

## Customer emails

Every shop sends WooCommerce's own order emails, coloured from the shop config on each sync
(header colour, PNG logo, supplier footer). On Pro and Complete the portal also sends `emails`
(`includes/emails.php`):

- **Order email:** the "processing" and "on hold" emails use `templates/emails/customer-order.php`
  with the shop's heading, message, "What we need from you" and "What happens next", then the order
  table and the documents box as usual.
- **Review request:** scheduled with Action Scheduler when an order is completed, sent once after
  `days` (3) if the order is still complete. Marked with `_pbb_review_sent`.

`{first_name}`, `{order_number}` and `{shop_name}` are filled in per order.

## File map

| File | Purpose |
|---|---|
| `3d-plate-builder.php` | Registers Three.js and the app as ES modules, adds the shortcode |
| `templates/builder.php` | Viewport markup and toolbar |
| `assets/js/main.js` | Boots each builder on the page and wires the toolbar |
| `assets/js/plate-viewport.js` | Renderer, studio lighting, camera framing, orbit controls, reset tween, on-demand render loop |
| `assets/js/plate-model.js` | Plate geometry (520 × 111 mm, rounded, bevelled 3.2 mm acrylic), materials, legal footer artwork |
| `assets/js/plate-font.js` | Loads the glyph JSON and turns outlines into Three.js shapes |
| `assets/js/plate-layout.js` | Input cleaning, DVLA auto-spacing and the legal mm layout (pure functions, no Three.js) |
| `assets/js/plate-styles.js` | Character styles and effect colours, as data |
| `assets/js/plate-text.js` | Builds each style's layers (print, dome, laser-cut, pyramid), materials and animation |
| `assets/js/builder-ui.js` | Style and colour pickers, rendered from the style data |
| `assets/js/plate-sizes.js` | The nine plate sizes and their outlines (rounded, hex) |
| `assets/js/plate-badges.js` | UK badge, EV strip and border options, and where each is offered |
| `assets/fonts/charles-wright-bold.json` | Standard 79 mm characters (generated, see below) |
| `assets/fonts/charles-wright-singapore.json` | 64 mm characters for motorbike, import and trailer plates |
| `assets/js/pricing.js` | Live price (mirrors the PHP price maths) |
| `assets/js/basket.js` | Plates, extras, price summary, Add to basket |
| `includes/catalog.php` | Server-side option list, validation, price maths, order descriptions |
| `includes/settings.php` | WooCommerce → Plate Builder pricing screen |
| `includes/cart.php` | Add-to-basket endpoint, basket/checkout display, order meta |
| `includes/documents.php` | DVLA document checks: types, private storage, uploads, checkout validation, Awaiting documents status, customer instructions, cleanup |
| `includes/documents-admin.php` | Order-screen documents panel, private downloads, verification, document contact settings |
| `assets/js/documents.js` / `documents-blocks.js` | Checkout "Your documents" panel (classic and block checkout) |
| `assets/css/documents.css` | Documents panel styles |
| `assets/css/builder.css` | Builder UI |

## Legal character layout

Characters are placed by their ink edges, using the figures in `CHARACTER_SPECS` (`plate-layout.js`):

| | Standard (2001) |
|---|---|
| Character height | 79 mm |
| Character width | 50 mm (`1` and `I`: 14 mm) |
| Stroke | 14 mm |
| Gap between characters | 11 mm |
| Gap between groups | 33 mm |
| Minimum margins | 11 mm |

We don't use the font's own space character: it adds the sidebearings on top of its 33 mm, which gives a 44 mm gap.

Typing without a space auto-spaces current (`AB12 CDE`), prefix (`A123 BCD`) and suffix (`ABC 123D`) registrations. Anything else (dateless, cherished) is shown exactly as typed.

## Character styles

Each style is a stack of layers in `plate-styles.js`. Adding a style means adding one entry, with no engine changes:

| Style | Layers | Badge |
|---|---|---|
| Printed | flat ink under the acrylic | Road legal |
| 3D Gel | 2.2 mm domed resin | Road legal |
| 4D 3mm | laser-cut acrylic, 0.25 mm polished edge | Road legal |
| 4D 6mm | two 3 mm acrylic layers (6 mm) | Road legal |
| 4D Gel 3mm | 3 mm acrylic with a 1.8 mm gel dome on top | Road legal |
| 4D Gel 6mm | 6 mm acrylic with a 1.8 mm gel dome on top | Road legal |
| Retro | 5 mm pressed chamfer: 0.8 mm vertical edge rising to a ~2 mm ridge (height from distance to the letter edge, so sharp corners fold cleanly) | Road legal |

Borders are black only, printed 3 mm inside the plate edge, at 1, 2 or 3 mm thick (`BORDERS` in `plate-badges.js`).

Every style keeps the character's footprint on the plate at its legal size. Raised layers cast real shadows on the plate face.
The "Road legal" and "Show plate" badges follow BS AU 145e (2021): a single solid black, raised 3D/4D allowed, no borders, shadows or two-tone effects. **Check them against your own compliance advice before launch.**

## Plate sizes

| Size | mm | Lines | Characters | Notes |
|---|---|---|---|---|
| Standard | 520 × 111 | 1 | 79 mm Charles Wright Bold | |
| Short | cut to fit × 111 | 1 | 79 mm | Registration width + 11 mm each side (+ badge) |
| Hex | 520 × 111 | 1 | 79 mm | 28 mm pointed ends |
| 4x4 (square) | 279 × 203 | 2 | 79 mm, 19 mm line gap | Rear only |
| Oversized | 533 × 152 | 1 | 79 mm | Rear only |
| Motorbike | 229 × 178 | 2 | 64 mm Singapore, 13 mm line gap | Rear only |
| Trailer | 520 × 111 | 1 | 64 mm Singapore | Commercial trailer, rear only |
| Japanese import | 330 × 165 | 2 | 64 mm Singapore | |
| US import | 305 × 152 | 2 | 64 mm Singapore | 5.5 mm margins (152 mm can't fit 11 mm) |

Sizes are data in `plate-sizes.js`, so changing a dimension is a one-line edit.

### Front and rear sizes (1.9)

- **Front sizes:** Standard, Short, Hex, Japanese import, US import.
- **Rear sizes:** the same, plus Oversized and 4x4 (rear only).
- **Choosing:** the size step has a "Front plate / Rear plate" switch, tied to the preview's Front/Rear buttons, so the plate on screen is the one being sized. A "Same size front & rear" tick box (on by default) keeps them together.
- **Picking a rear-only size:** Oversized or 4x4 unlinks the sizes and shows the rear. The front stays as it was.
- **One-plate vehicles:** Motorbike, Trailer and Commercial trailer are a single rear plate.
- **Rear-only sizes:** Oversized, 4x4, Motorbike, Trailer and Commercial trailer are listed under Rear plate only, on every screen. The front list says where they are.
- **Phones:** Motorbike and trailer plates show as compact chips after the car sizes, so each list fits on the card.
- **Badges and styles:** they have to be available on both sizes.
- **Price:** each plate adds its own size surcharge.
- **Basket and orders:** show "Front Short (460 × 111 mm), Rear Oversized (533 × 152 mm)". The configuration stores `size` / `width` (front) and `rear_size` / `rear_width`.

## Badges

| Badge | Finish | Sizes |
|---|---|---|
| UK (Union Flag over blue "UK") | Printed or gel | Standard, Short, Oversized, Motorbike |
| EV strip | Printed or gel | Standard, Short |
| EV strip + UK | Gel | Standard, Short |
| UK 4D | Blue UK artwork on a raised 3 mm acrylic tile | Standard, Short, Oversized, Motorbike |

- **Badge placement:** on single-line plates the badge takes the left side and the registration is centred in the space that's left. On motorbikes the badge (30 mm wide) sits beside the bottom line only.
- **Gel finish:** gel adds a clear resin dome that refracts the print beneath it.
- **Size changes:** a badge that isn't offered on the newly chosen size is cleared automatically.
- **Custom flag artwork:** to use your own Union Flag file, add `add_filter( 'pbb_flag_artwork', fn () => array( 'uk' => 'https://…/flag.svg' ) );`.

## Pricing and basket

Default prices are typical UK prices and are edited in **WooCommerce → Plate Builder**:

| | Front & rear | One plate |
|---|---|---|
| Printed | £29.99 | £19.99 |
| 3D Gel, 4D 3mm | £49.99 | £29.99 |
| 4D 6mm, 4D Gel 3mm | £64.99 | £39.99 |
| 4D Gel 6mm | £79.99 | £44.99 |
| Retro | £69.99 | £39.99 |

- **One plate:** half the pair price, rounded up to the nearest £5, minus 1p, plus £5.
- **Sizes:** Short, Hex, Square, Oversized and import sizes add £10 per plate.
- **Motorbike and trailer plates:** £19.99, printed, one plate.
- **Badge and border:** charged once per basket line. Badges are UK printed £5, gel £10, 4D £15, EV printed £5, EV gel or EV + UK £10, and half that on motorbike and trailer plates. Border £10.
- **Extras (once per line):** 8 adhesive pads (1 mm) £2.50, XL pad set (8 × 1 mm + 3 × 5 mm) £6.99, screws + caps £2.50, standard frame holders £18 a set, frameless holders £20 a set. Only one pad option and one holder option per line (`accessory_groups`).
- **Where prices are stored:** in `pbb_pricing_v2`. 1.x prices (`pbb_pricing`) were per plate and aren't reused, except the extras.

**Basket**
- **Which product:** each design is added as the shop product that matches it, so reports, Google and the order screen show the real product. For example: EV badge → the EV product for that style; 4D 3mm Short → Short 4D; Printed Hex → Hex; Motorbike → Motorbike. Designs with no matching product use the hidden "Custom Number Plate" product. The matching table is `pbb_product_presets()`, which can be filtered.
- **Price:** always recalculated on the server from the saved design. It runs late (priority 999), so Product Add-Ons can't override it.
- **Basket, checkout and emails:** each line shows Registration, Plates, Size, Style, Badge, Border, Extras and Road legal / Show plate, plus the hidden JSON meta `_pbb_config`.
- **Option ids:** the PHP catalogue in `includes/catalog.php` must keep the same ids as the JS data files.

## Shop product pages

The products in `pbb_product_presets()` show the builder where the Product Add-Ons form was:
- **Starting point:** the builder starts on that product's style, size and badge. For example, EV 3D Gel opens on 3D Gel with the EV badge.
- **What's hidden:** the add-ons form and the theme's sticky add-to-cart bar are hidden with CSS (`body.pbb-product-page`). Everything else on the page is untouched: gallery, description, reviews, related products.
- **Phones:** a "Design your plates in 3D" button opens the builder full screen. The app bar's back arrow, or the phone's back button, returns to the product page.
- **Not changed:** Wedding plates and the adhesive pads product keep their usual pages.

To leave a product out, remove it with the filter:

```php
add_filter( 'pbb_product_presets', function ( $p ) { unset( $p[17642] ); return $p; } );
```

## Around the site

- **Design Your Plates page:** `/design-your-plates/`, created with the shortcode the first time an admin opens the dashboard. Its ID is kept in `pbb_builder_page_id`.
- **Home and landing pages:** the full builder, in a "Design your plates in 3D" section after the page's first Elementor section (the top banner). Change the pages with the `pbb_featured_pages` filter (page IDs; default: the front page and the config's `landing_pages`). Phones get the launch button.
- **Main menu:** a "3D Plate Builder" item after Home, on the `main-menu-left` menu (`pbb_menu_slugs` filter).
- **Floating "Design in 3D" button:** bottom left on every other page. Not shown on the basket, checkout, account, builder or featured pages, or on builder product pages.
- **Product pages:** a "Design in 3D / Classic plate builder" switch above the builder. The registration carries across, and the choice is remembered on that device.

## Phones: app layout

On screens up to 760 px wide, the builder fills the screen like an app:
- a dark top bar with a back arrow (to the previous page on the site, or the home page), the logo and the basket with its item count;
- the 3D preview under the bar;
- the steps as swipeable cards with a progress bar, each card scrolling on its own;
- the total and Next / Add to basket fixed at the bottom; "Added to basket" shows as a toast.

The layout follows the visible screen, so the phone keyboard never covers the buy bar. To keep the builder inside the page on phones instead, use `[plate_builder mobile="inline"]`. Use `mobile="launch"` for a button that opens it, as on product pages.

## Preview controls

- **Turn:** one finger or the mouse. Turns right round, so the back of the plate (plain sheeting, or the `backing` print) can be seen. Car view turns freely.
- **Zoom:** pinch, or scroll the mouse wheel. Zoom stays centred, from 0.25× to 1.25× of the framed view.
- **Pan:** two fingers or a right-drag. The view always stays on the plate.
- **Double-tap or double-click:** zooms in on that spot; doing it again returns to the whole plate.
- **Desktop scrolling:** scrolling over the preview scrolls the page until the customer clicks the plate (or holds Ctrl / ⌘, which trackpad pinches send too). If they scroll first, a hint says how to zoom.
- **Steps:** Your reg & style (the style tiles sit under the reg box), Plate size (with Front & rear / Front only / Rear only), Badge, Border, Add-ons.
- **In-store fitting:** an add-on (price on the Plate Builder screen, shown in the builder total as "added at checkout"). The design stores `fitting`, the basket picks Collect, and checkout ticks its own "Fit my plates while I wait" option, which charges the £30. If the customer chooses delivery, fitting drops off with a note.
- **Choosing a character style:** eases in close at an angle to show its profile. The next step, or Reset, returns to the full view.

## After adding to basket

A pop-up confirms "Added to basket" with the registration and price, and goes to checkout by itself after 4 seconds. "Go to checkout" goes straight away. "Add another set" stays in the builder, and "View basket" opens the basket.

## DVLA document checks

As a Registered Number Plate Supplier, the shop must see one proof of ID and one proof of entitlement to the registration before dispatching or handing over road-legal plates. Show plates are exempt.

**At checkout**
- **When it appears:** a "Your documents" panel shows only when the basket holds a road-legal builder plate. On the classic checkout it sits under the customer details; on the block checkout it sits in the order summary (WooCommerce's order-meta slot).
- **Upload now:** the customer picks the document type (the same lists as the shop's /valid-documents/ page) and uploads a photo or PDF for each: JPG, PNG, WebP, HEIC or PDF, up to 10 MB. The server checks the real file contents, not just the extension.
- **Send after ordering:** the customer is told to email, WhatsApp or post them, or bring them when collecting. Set these contact details in WooCommerce → Plate Builder → Documents (email defaults to the shop's "From" address).
- **Checks before ordering:** the server checks the choice before the order is placed. Upload-now orders need both documents.
- **Failed payments:** uploads survive a failed card payment, so the customer doesn't have to upload again.

**After ordering**
- **Order status:** paid orders that need documents move to **Awaiting documents** instead of Processing. This applies to any gateway, and also when staff mark a bank transfer as paid. The status counts as paid in reports.
- **Customer instructions:** customers who chose "send later" see instructions on the order-received page and in their order email.
- **Staff panel:** the order screen has a **Plate documents (DVLA)** panel. It shows what the customer chose and private download links for any uploads. Staff record which ID and entitlement document they saw, tick the confirmation and click **Update**. The order moves to Processing, and the record (who verified, when, which documents) is added to the order and an order note.

**Privacy**
- **Where files are kept:** files never go in the media library. On Plesk they're stored in the site's `private` folder, outside `httpdocs`, which the web server never serves. Elsewhere they go in `wp-content/uploads/pbb-documents/` with a deny-all `.htaccess` and random 40-character names. Override with the `pbb_documents_dir` filter.
- **Who can download:** only staff with order access, through a signed admin link.
- **Automatic cleanup:** a daily job deletes abandoned checkout uploads after 2 days, and order files 30 days after verification (`pbb_documents_retention_days` filter, 0 = keep). The verification record stays on the order. **Confirm the retention period with your own data-protection/RNPS record-keeping advice.**

**Page-builder checkouts (tested on a ShopBuilder/Elementor shop, 1.1.2)**
- **Where the panel goes:** some shops' checkout and thank-you pages are built from ShopBuilder/Elementor widgets. These never run WooCommerce's `woocommerce_checkout_after_customer_details` or `woocommerce_thankyou` hooks. If no placeholder exists, the panel script places itself after the billing details. Without that fix, every road-legal order was rejected.
- **When documents are cleared:** as soon as the order they went with is no longer pending or failed, not on the thank-you page. Otherwise a customer's next order (for a different registration) reused the previous order's documents.

**Filters:** `pbb_document_types` (edit the accepted document lists), `pbb_documents_dir`, `pbb_documents_retention_days`.

**Not testable without WordPress:** the PHP (uploads, checkout validation, order status, admin panel, emails, cleanup) hasn't run yet. Only the checkout panel's browser behaviour was checked, against a stand-in server (`dev/documents.html`). Before go-live, test on a staging site:
1. A road-legal order with uploads.
2. A send-later order.
3. A show-plate-only order (no panel).
4. A failed then successful card payment.
5. Staff verification moving the order to Processing.
6. A document download link opened while logged out (it must be refused).

## Rebuilding the font

The website never serves the `.ttf`. `tools/build-font.py` (outside the plugin folder) exports just A-Z and 0-9 as millimetre outlines:

```bash
pip install fonttools
python3 tools/build-font.py CharlesWright-Bold.ttf builder/assets/fonts/charles-wright-bold.json --cap-height 79 --name "Charles Wright Bold"
```

For the motorcycle font: `python3 tools/build-font.py CharlesWrightSingapore.otf builder/assets/fonts/charles-wright-singapore.json --cap-height 64 --name "Charles Wright Singapore"`. Both TTF and OTF fonts work.

**Licence:** check that your K-Type licence covers web or app embedding. Outlines served to the browser still count as using the font on the web.

## Notes

- **Units:** 1 scene unit = 1 mm, so later steps can position characters with legal measurements directly.
- **Performance:** frames render only while the user drags, the view settles or an animation plays. An idle builder uses no GPU time.
- **Production:** Three.js loads from jsDelivr, pinned to r170. Before launch, copy the three files into `assets/vendor/three/` and change the URLs in `pbb_register_assets()`.
- **Local testing without WordPress:** from the folder that contains `builder/` and `dev/`, run `python3 -m http.server 8765`, then open `http://localhost:8765/dev/`.
