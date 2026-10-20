<?php

namespace App\Support;

use App\Models\Shop;

/**
 * Everything a shop's website takes from the portal, in one array. Publish freezes a copy on
 * shops.published_config; the plugin fetches that copy from /api/v1/site with its licence key.
 *
 * Files appear as storage paths here; the API swaps them for short-lived download links.
 */
class SiteConfig
{
    public static function build(Shop $shop): array
    {
        $catalogue = config('portal.catalogue');
        $builder = BuilderSettings::forShop($shop);

        $products = [];
        if ($shop->canAddProducts()) {
            foreach ($shop->shopProducts()->where('active', true)->get() as $p) {
                $plate = $p->isPlate();
                $products[] = array_filter([
                    'id' => $p->id,
                    'type' => $p->type,
                    'name' => $p->name,
                    'description' => (string) $p->description,
                    // Plate products sell at the builder's price; this is the "from" price on the product page.
                    'price' => $plate ? round((float) ($builder['styles'][$p->style]['pair'] ?? 0), 2) : round(($p->price_pence ?? 0) / 100, 2),
                    'preset' => $plate ? array_filter(['style' => $p->style, 'size' => $p->size, 'badge' => $p->badge]) : null,
                    'images' => array_values($p->images ?? []),
                ], fn ($v) => $v !== null);
            }
        }

        return [
            'shop' => [
                'name' => $shop->trading_name ?: $shop->name,
                'supplier' => (string) $shop->supplier_name,
                'postcode' => strtoupper((string) $shop->supplier_postcode),
                'email' => (string) $shop->contact_email,
                'accent' => $shop->accent_colour ?: null,
                'accent_ink' => $shop->accent_colour ? self::inkFor($shop->accent_colour) : null,
                'dark' => $shop->dark_colour ?: null,
                'logo' => $shop->logo_path ?: null,
                'address' => (string) $shop->collection_address,
                'heading_font' => $shop->heading_font ?: null,
            ],
            // The builder product (every plan) and its options.
            'builder' => BuilderSettings::forSite($shop) + ['from_price' => BuilderSettings::fromPrice($shop)],
            'products' => $products,
            'emails' => EmailSettings::forSite($shop),
            'labels' => [
                'styles' => array_map(fn ($s) => $s['label'], $catalogue['styles']),
            ],
        ];
    }

    /** Dark or white text, whichever reads better on the brand colour. */
    public static function inkFor(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return '#0D0E10';
        }
        [$r, $g, $b] = array_map(fn ($c) => hexdec($c) / 255, str_split($hex, 2));
        $lin = fn ($c) => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        $l = 0.2126 * $lin($r) + 0.7152 * $lin($g) + 0.0722 * $lin($b);

        return $l > 0.179 ? '#0D0E10' : '#FFFFFF';
    }
}
