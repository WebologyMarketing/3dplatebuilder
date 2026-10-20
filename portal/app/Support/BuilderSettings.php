<?php

namespace App\Support;

use App\Models\Shop;
use Illuminate\Support\Str;

/**
 * A shop's builder settings: what's switched on and what it costs.
 *
 * Stored on shops.builder as:
 *   styles  {id: {on, pair, single}}      sizes, badges, borders, extras  {id: {on, price}}
 *   fitting "30.00"                       custom  [{id, name, price, on}]  (their own add-ons)
 * Prices are strings in pounds, like "29.99". Anything missing falls back to the catalogue default.
 */
class BuilderSettings
{
    public const GROUPS = ['sizes', 'badges', 'borders', 'extras'];

    /** The shop's settings merged over the catalogue defaults. New shops start with everything on. */
    public static function forShop(Shop $shop): array
    {
        $c = config('portal.catalogue');
        $saved = $shop->builder ?? [];
        $out = ['styles' => [], 'fitting' => $saved['fitting'] ?? $c['fitting'], 'custom' => array_values($saved['custom'] ?? [])];

        foreach ($c['styles'] as $id => $d) {
            $s = $saved['styles'][$id] ?? [];
            $out['styles'][$id] = [
                'on' => ! empty($d['always']) || (bool) ($s['on'] ?? true),
                'pair' => $s['pair'] ?? $d['pair'],
                'single' => $s['single'] ?? $d['single'],
            ];
        }
        foreach (self::GROUPS as $group) {
            foreach ($c[$group] as $id => $d) {
                $s = $saved[$group][$id] ?? [];
                $out[$group][$id] = [
                    'on' => ! empty($d['always']) || (bool) ($s['on'] ?? true),
                    'price' => $s['price'] ?? $d['price'],
                ];
            }
        }

        return $out;
    }

    /** Validation rules for the builder form. */
    public static function rules(): array
    {
        $price = ['nullable', 'numeric', 'min:0', 'max:9999'];
        $rules = [
            'styles' => ['array'], 'styles.*.on' => ['nullable', 'boolean'], 'styles.*.pair' => $price, 'styles.*.single' => $price,
            'fitting' => $price,
            'custom' => ['array', 'max:'.config('portal.max_custom_addons')],
            'custom.*.id' => ['nullable', 'string', 'max:20'],
            'custom.*.name' => ['nullable', 'string', 'max:80'],
            'custom.*.price' => $price,
            'custom.*.on' => ['nullable', 'boolean'],
        ];
        foreach (self::GROUPS as $g) {
            $rules[$g] = ['array'];
            $rules["$g.*.on"] = ['nullable', 'boolean'];
            $rules["$g.*.price"] = $price;
        }

        return $rules;
    }

    /**
     * Clean form input into the stored shape, over the shop's current settings ($current, from forShop()).
     * Unknown ids are dropped; a blank price goes back to the default; anything not sent stays as it was.
     */
    public static function fromInput(array $data, array $current): array
    {
        $c = config('portal.catalogue');
        $money = fn ($in, $key, $now, $default) => ! array_key_exists($key, $in) ? $now
            : ($in[$key] === null || $in[$key] === '' ? $default : number_format((float) $in[$key], 2, '.', ''));
        $on = fn ($in, $d, $now) => ! empty($d['always']) || (array_key_exists('on', $in) ? ! empty($in['on']) : $now);

        $out = ['styles' => [], 'fitting' => $money($data, 'fitting', $current['fitting'], $c['fitting']), 'custom' => []];
        foreach ($c['styles'] as $id => $d) {
            $in = $data['styles'][$id] ?? [];
            $now = $current['styles'][$id];
            $out['styles'][$id] = [
                'on' => $on($in, $d, $now['on']),
                'pair' => $money($in, 'pair', $now['pair'], $d['pair']),
                'single' => $money($in, 'single', $now['single'], $d['single']),
            ];
        }
        foreach (self::GROUPS as $group) {
            foreach ($c[$group] as $id => $d) {
                $in = $data[$group][$id] ?? [];
                $now = $current[$group][$id];
                $out[$group][$id] = ['on' => $on($in, $d, $now['on']), 'price' => $money($in, 'price', $now['price'], $d['price'])];
            }
        }
        if (! array_key_exists('custom', $data)) {
            $out['custom'] = $current['custom'];

            return $out;
        }
        foreach ($data['custom'] as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue; // an empty row is how you remove one
            }
            $id = (string) ($row['id'] ?? '');
            $out['custom'][] = [
                // Ids are kept, so past orders still read right after a rename.
                'id' => preg_match('/^c_[a-z0-9]{6}$/', $id) ? $id : 'c_'.Str::lower(Str::random(6)),
                'name' => $name,
                'price' => $money($row, 'price', '0.00', '0.00'),
                'on' => ! empty($row['on']),
            ];
        }

        return $out;
    }

    /**
     * The settings in the plugin's own shape (see plugin/includes/portal-sync.php):
     * which options are offered, the pricing table, and the add-on names.
     */
    public static function forSite(Shop $shop): array
    {
        $b = self::forShop($shop);
        $c = config('portal.catalogue');
        $on = fn (array $group) => array_keys(array_filter($group, fn ($o) => $o['on']));
        $num = fn ($v) => round((float) $v, 2);

        $pricing = ['style_pair' => [], 'style_single' => [], 'size' => [], 'flat' => [], 'badge' => [], 'border' => [], 'accessories' => [], 'services' => ['fitting' => $num($b['fitting'])]];
        foreach ($b['styles'] as $id => $s) {
            $pricing['style_pair'][$id] = $num($s['pair']);
            $pricing['style_single'][$id] = $num($s['single']);
        }
        foreach ($b['sizes'] as $id => $s) {
            $pricing[$c['sizes'][$id]['type'] === 'flat' ? 'flat' : 'size'][$id] = $num($s['price']);
        }
        foreach (['badges' => 'badge', 'borders' => 'border', 'extras' => 'accessories'] as $group => $key) {
            foreach ($b[$group] as $id => $s) {
                $pricing[$key][$id] = $num($s['price']);
            }
        }

        $accessories = [];
        foreach ($b['extras'] as $id => $s) {
            if ($s['on']) {
                $accessories[$id] = $c['extras'][$id]['label'];
            }
        }
        foreach ($b['custom'] as $a) {
            if ($a['on']) {
                $accessories[$a['id']] = $a['name'];
                $pricing['accessories'][$a['id']] = $num($a['price']);
            }
        }

        return [
            'offer' => [
                'styles' => $on($b['styles']),
                'sizes' => $on($b['sizes']),
                'badges' => array_merge(['none'], $on($b['badges'])),
                'borders' => array_merge(['none'], $on($b['borders'])),
            ],
            'pricing' => $pricing,
            'accessories' => $accessories,
        ];
    }

    /** The lowest pair price among styles that are on: the "from" price on the shop. */
    public static function fromPrice(Shop $shop): float
    {
        $prices = collect(self::forShop($shop)['styles'])->where('on', true)->pluck('pair')->map(fn ($p) => (float) $p);

        return (float) ($prices->min() ?? 0);
    }
}
