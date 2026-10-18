# PlateBoss Checkout Notices Fix

## The problem
The checkout page is a ShopBuilder template (Elementor post 30323) on both staging and live. When WooCommerce returns a checkout error, ShopBuilder 3.4.2 deletes WooCommerce's error box and tries to move it into a ShopBuilder "Notice" widget. The template has no Notice widget, so the error is thrown away. ShopBuilder's CSS also hides any error box placed directly inside its checkout form.

Result: when the terms box is unticked, a field is missing, or a document upload is missing, nothing happens when the customer clicks Place order.

## Fix A (no code): add the Notice widget
1. WordPress admin > ShopBuilder > Templates > the Checkout template (Elementor post 30323) > Edit with Elementor.
2. Drag ShopBuilder's Notice widget above the billing form and save.
3. Test (below). If this works, Fix B isn't needed.

## Fix B: this plugin
Once every other `checkout_error` handler has run, it checks whether an error box is visible. If none is, it puts the error just above the checkout form and scrolls to it. If a Notice widget is added later, the plugin does nothing.

Install on a site:
1. Plesk > WP Toolkit > the site > Plugins > Upload plugin > `plateboss-checkout-notices.zip` > Activate.
   (Or WordPress admin > Plugins > Add New > Upload Plugin.)
2. Clear the LiteSpeed cache.

To undo: deactivate "PlateBoss Checkout Notices Fix".

## Test (this can't create an order)
1. Add any plate to the basket and open Checkout.
2. Leave "I have read and agree to the terms" unticked and click Place order.
3. A red box saying "Please read and accept the terms and conditions…" should appear above the form, and the page should scroll to it.
4. On staging, also choose "upload documents" with no files attached. The box should show "Please upload your proof of id…".
