<?php

/*
 * 3dPlateBuilder.com customer portal settings.
 * Prices are in pence. Stripe price ids come from .env so the sandbox and live accounts can differ.
 * Homee Ltd is not VAT registered yet: no VAT is added to anything here.
 */

return [

    'admin_email' => env('PORTAL_ADMIN_EMAIL', 'hello@3dplatebuilder.com'),

    'plans' => [
        'starter' => [
            'name' => 'Starter',
            'monthly' => 9900,
            'setup' => 39500,
            'stripe_monthly' => env('STRIPE_PRICE_STARTER'),
            'stripe_setup' => env('STRIPE_PRICE_SETUP_STARTER'),
            'summary' => 'A one-page shop with the builder, every product chosen inside it.',
        ],
        'pro' => [
            'name' => 'Pro',
            'monthly' => 19900,
            'setup' => 69500,
            'stripe_monthly' => env('STRIPE_PRICE_PRO'),
            'stripe_setup' => env('STRIPE_PRICE_SETUP_PRO'),
            'summary' => 'A landing page for each of the seven styles, up to 15 pages, the app-like phone layout and the car view.',
        ],
        'complete' => [
            'name' => 'Complete',
            'monthly' => 39900,
            'setup' => 149900,
            'stripe_monthly' => env('STRIPE_PRICE_COMPLETE'),
            'stripe_setup' => env('STRIPE_PRICE_SETUP_COMPLETE'),
            'summary' => 'Up to 30 pages, a blog, checkout inside the builder and document checks.',
        ],
    ],

    // Bought outright: arranged by hand (payment link from the admin), then billed yearly for hosting.
    'outright' => ['name' => 'Outright', 'price' => 1000000, 'yearly' => 95000],

    'minimum_term_months' => 12,
    'notice_days' => 30,

    'addons' => [
        'addon_migration' => ['name' => 'Move my current shop over', 'note' => 'We copy your products, customers and orders.', 'price' => 49500, 'stripe' => env('STRIPE_PRICE_ADDON_MIGRATION')],
        'addon_custom_style' => ['name' => 'A custom plate style', 'note' => 'A new style designed for your range.', 'price' => 39500, 'stripe' => env('STRIPE_PRICE_ADDON_CUSTOM_STYLE')],
        'addon_plate_logo' => ['name' => 'My logo on the plate', 'note' => 'Your logo and footer line on each plate.', 'price' => 12500, 'stripe' => env('STRIPE_PRICE_ADDON_PLATE_LOGO')],
    ],
    'design_hourly' => 6000,

    // Which builder features each plan includes (for the "on your plan" list).
    'features' => [
        ['label' => 'Front and rear sizes', 'from' => 'starter'],
        ['label' => 'Badges and borders', 'from' => 'starter'],
        ['label' => 'Car view', 'from' => 'pro'],
        ['label' => 'App-like phone layout', 'from' => 'pro'],
        ['label' => 'Style landing pages', 'from' => 'pro'],
        ['label' => 'Checkout inside the builder', 'from' => 'complete'],
        ['label' => 'Document checks', 'from' => 'complete'],
        ['label' => 'Blog', 'from' => 'complete'],
    ],

    // What a plate maker can switch on and price in their builder. Kept in step with
    // plugin/includes/catalog.php (option ids) and pbb_default_pricing() (starting prices, in pounds).
    // `always` options can't be switched off: the builder falls back to them.
    'catalogue' => [
        'styles' => [
            'printed' => ['label' => 'Printed', 'pair' => '29.99', 'single' => '19.99', 'always' => true],
            'gel-3d' => ['label' => '3D Gel', 'pair' => '49.99', 'single' => '29.99'],
            'acrylic-4d-3' => ['label' => '4D 3mm', 'pair' => '49.99', 'single' => '29.99'],
            'acrylic-4d-6' => ['label' => '4D 6mm', 'pair' => '64.99', 'single' => '39.99'],
            'gel-4d-3' => ['label' => '4D Gel 3mm', 'pair' => '64.99', 'single' => '39.99'],
            'gel-4d-6' => ['label' => '4D Gel 6mm', 'pair' => '79.99', 'single' => '44.99'],
            'retro' => ['label' => 'Retro', 'pair' => '69.99', 'single' => '39.99'],
        ],
        // surcharge: added per plate on top of the style price. flat: one printed plate at this price.
        'sizes' => [
            'standard' => ['label' => 'Standard', 'mm' => '520 × 111', 'price' => '0.00', 'type' => 'surcharge', 'always' => true],
            'short' => ['label' => 'Short', 'mm' => 'cut to fit', 'price' => '10.00', 'type' => 'surcharge'],
            'hex' => ['label' => 'Hex', 'mm' => 'cut to fit', 'price' => '10.00', 'type' => 'surcharge'],
            'square' => ['label' => '4x4', 'mm' => '279 × 203, rear', 'price' => '10.00', 'type' => 'surcharge'],
            'oversized' => ['label' => 'Oversized', 'mm' => '533 × 152, rear', 'price' => '10.00', 'type' => 'surcharge'],
            'japanese-import' => ['label' => 'Japanese import', 'mm' => '330 × 165', 'price' => '10.00', 'type' => 'surcharge'],
            'us-import' => ['label' => 'US import', 'mm' => '305 × 152', 'price' => '10.00', 'type' => 'surcharge'],
            'motorbike' => ['label' => 'Motorbike', 'mm' => '229 × 178', 'price' => '19.99', 'type' => 'flat'],
            'trailer' => ['label' => 'Trailer', 'mm' => '520 × 111', 'price' => '19.99', 'type' => 'flat'],
            'trailer-commercial' => ['label' => 'Commercial trailer', 'mm' => '229 × 178, white', 'price' => '19.99', 'type' => 'flat'],
        ],
        'badges' => [
            'uk' => ['label' => 'UK badge (printed)', 'price' => '5.00'],
            'uk-gel' => ['label' => 'UK gel badge, white', 'price' => '10.00'],
            'uk-gel-blue' => ['label' => 'UK gel badge, blue', 'price' => '10.00'],
            'uk-4d' => ['label' => 'UK 4D badge (raised)', 'price' => '15.00'],
            'ev' => ['label' => 'EV strip (printed)', 'price' => '5.00'],
            'ev-gel' => ['label' => 'EV gel badge', 'price' => '10.00'],
            'ev-uk-gel' => ['label' => 'EV gel badge with UK', 'price' => '10.00'],
        ],
        'borders' => [
            'black-1mm' => ['label' => 'Black border, 1 mm', 'price' => '10.00'],
            'black-2mm' => ['label' => 'Black border, 2 mm', 'price' => '10.00'],
            'black-3mm' => ['label' => 'Black border, 3 mm', 'price' => '10.00'],
        ],
        'extras' => [
            'sticky_pads' => ['label' => '8 adhesive pads (1 mm)', 'price' => '2.50'],
            'xl_pads' => ['label' => 'XL pad set (8 × 1 mm + 3 × 5 mm)', 'price' => '6.99'],
            'screw_kit' => ['label' => 'Screws + caps (4 screws, 2 yellow & 2 white caps)', 'price' => '2.50'],
            'frameless_holders' => ['label' => 'Frameless holders (set)', 'price' => '20.00'],
        ],
        'fitting' => '30.00',
    ],

    // Your own add-ons and products (Pro and Complete).
    'max_custom_addons' => 20,
    'max_products' => 60,
    'max_product_images' => 6,

    'fonts' => ['Archivo', 'Barlow Condensed', 'Montserrat', 'Poppins', 'Geist'],

    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
];
