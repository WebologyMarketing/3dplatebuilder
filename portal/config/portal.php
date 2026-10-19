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

    // Kept in step with plugin/includes/catalog.php option ids.
    'catalogue' => [
        'styles' => [
            'printed' => 'Printed', 'gel-3d' => '3D Gel', 'acrylic-4d-3' => '4D 3mm', 'acrylic-4d-6' => '4D 6mm',
            'gel-4d-3' => '4D Gel 3mm', 'gel-4d-6' => '4D Gel 6mm', 'retro' => 'Retro',
        ],
        'sizes' => [
            'standard' => 'Standard', 'short' => 'Short', 'hex' => 'Hex', 'square' => '4x4', 'oversized' => 'Oversized',
            'motorbike' => 'Motorbike', 'trailer' => 'Trailer', 'trailer-commercial' => 'Commercial trailer',
            'japanese-import' => 'Japanese import', 'us-import' => 'US import',
        ],
        'extras' => [
            'uk' => 'UK badge', 'uk-gel' => 'UK gel badge, white', 'uk-gel-blue' => 'UK gel badge, blue', 'uk-4d' => 'UK 4D badge',
            'ev' => 'EV strip', 'ev-gel' => 'EV gel badge', 'ev-uk-gel' => 'EV gel badge with UK',
            'black-1mm' => 'Border 1 mm', 'black-2mm' => 'Border 2 mm', 'black-3mm' => 'Border 3 mm',
            'sticky_pads' => 'Adhesive pads', 'xl_pads' => 'XL pad set', 'screw_kit' => 'Screw kit', 'frameless_holders' => 'Frameless holders',
        ],
    ],

    'fonts' => ['Archivo', 'Barlow Condensed', 'Montserrat', 'Poppins', 'Geist'],

    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
];
