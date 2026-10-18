# Plate builder kit: what's in here

This is the master copy of the 3D number plate builder built for PlateBoss.co.uk (plugin version 1.9.19), packaged so a new website can showcase it as a product for other UK number plate makers.

## Folders

- `plateboss-builder/`: the WordPress/WooCommerce plugin (PHP + ES-module JavaScript + Three.js r170 from the jsDelivr CDN). `README.md` inside documents it in full.
- `dev/index.html`: a standalone demo page that runs the whole 3D builder in a browser with no WordPress (open it through a local web server, e.g. `python3 tools/nocache_server.py` from the repo root, then http://127.0.0.1:8765/plugin-dev/). Add to basket shows what would be sent instead of calling a shop. URL options: `?mobile=app|launch|inline`, `?style=retro`, `?size=short`, `?badge=uk`.
- `dev/documents.html`: demo of the proof-of-ID / entitlement document step.
- `extras/plateboss-checkout-notices/`: small plugin that makes checkout error messages visible on ShopBuilder (Elementor) checkouts.
- `extras/plateboss-express-pay/`: small plugin that puts Stripe Apple Pay / Google Pay below "Proceed to checkout" and "Place order".
- `tools/`: `nocache_server.py` (local server for the demo), `build-font.py` (turns a font file into the JSON glyphs the builder uses).

## What the builder does (all live on plateboss.co.uk)

- Real-time 3D preview (WebGL): drag to turn 360° on the plate's centre, pinch / double-tap zoom, front (white) and rear (yellow) plates, "on a car" view.
- Legal layout: UK registration spacing and the Charles Wright character font applied as you type, with road-legal / show-plate status; supplier name, postcode and BS AU 145e marking in the footer.
- Character styles: printed, 3D gel, 4D 3 mm, 4D 6 mm, 4D gel 3 mm, 4D gel 6 mm, retro (pressed chamfer), each modelled in 3D with studio reflections (hexagon detailing-bay lights).
- Sizes: standard, short, hex, Japanese import, US import; rear-only oversized, 4x4, motorbike, trailer, commercial trailer; different front and rear sizes.
- Badges (UK printed, UK gel white/blue, UK 4D raised, EV strip, EV gel, EV + UK) and borders (1/2/3 mm), with the camera zooming onto the badge.
- Add-ons (pads, screws, frameless holders), in-store fitting, live pricing that matches the shop's price list (prices editable in WP admin).
- Phone app layout: full-screen, step cards (reg & style, plate size, badge, border, add-ons), progress bar, sticky buy bar.
- Checkout inside the builder: the shop's real WooCommerce checkout in the builder (delivery cards, collect-in-store, documents, card, Apple Pay, Google Pay, Klarna hand-off), then order confirmation.
- Proof of ID / entitlement documents: upload at checkout or send later, "Awaiting documents" order status, staff checks, customer instructions on the thank-you page and in emails.
- Site placement: builder on every product page with a "Design in 3D / Classic plate builder" switch, a Design Your Plates page, menu item, floating button, landing-page and category promos.

## PlateBoss-specific parts to white-label for another shop

- `pbb_supplier_details()` in `plateboss-builder.php`: supplier name, postcode, logo (filter `pbb_supplier_details`).
- `includes/catalog.php`: `pbb_product_presets()` maps PlateBoss product IDs to styles; product matching for the basket; default price list.
- `includes/placement.php`: landing page IDs, category slugs, menu slug (all filterable).
- Delivery cards and fitting at checkout come from PlateBoss's own WPCode snippets (not in this kit).
- Logos and the Nikkalite backing print (`plate-model.js`) are PlateBoss/Nippon Carbide marks.

## Check before selling

- Licence for the Charles Wright font data in `assets/fonts/` (it was made from font files supplied by PlateBoss).
- Licences for any 3D car models used in the "On a car" view (`car-model.js` loads bought models).
- Three.js is MIT-licensed (fine to resell with notice).
- Any wording about legality: the builder helps with BS AU 145e layout, but the plate maker stays responsible for compliance and DVLA registration (RNPS).
