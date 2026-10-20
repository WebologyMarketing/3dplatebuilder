<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * What a shop's website pulls from the portal (plugin/includes/portal-sync.php).
 *
 *   GET  /api/v1/site            the last published settings, products and brand, with image links
 *   POST /api/v1/site/applied    the website reports the version it now has
 *   GET  /api/v1/media           one image or logo (signed link, 2 hours)
 *
 * The licence key goes in the X-Licence-Key header.
 */
class SiteController extends Controller
{
    public function show(Request $request)
    {
        $shop = $this->shop($request);
        $shop->forceFill(['site_checked_at' => now()])->saveQuietly();

        if (! $shop->published_version) {
            return response()->json(['version' => 0]);
        }

        $config = $shop->published_config;
        $file = fn (?string $path) => $path ? ['key' => basename($path), 'url' => $this->link($shop, $path)] : null;

        $config['shop']['logo'] = $file($config['shop']['logo'] ?? null);
        foreach ($config['products'] as $i => $p) {
            $config['products'][$i]['images'] = array_values(array_filter(array_map($file, $p['images'] ?? [])));
        }

        return response()->json(['version' => $shop->published_version, 'published_at' => $shop->published_at?->toIso8601String()] + $config);
    }

    public function applied(Request $request)
    {
        $shop = $this->shop($request);
        $version = (int) $request->input('version');
        $shop->forceFill(['site_version' => min($version, $shop->published_version), 'site_checked_at' => now()])->saveQuietly();

        return response()->json(['ok' => true]);
    }

    public function media(Request $request)
    {
        $shop = Shop::findOrFail((int) $request->query('shop'));
        $path = (string) base64_decode(strtr((string) $request->query('f'), '-_', '+/'), true);
        abort_unless(str_starts_with($path, "shops/{$shop->id}/") && ! str_contains($path, '..') && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, basename($path), [
            'Cache-Control' => 'private, max-age=600',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
        ]);
    }

    private function link(Shop $shop, string $path): string
    {
        return URL::temporarySignedRoute('api.media', now()->addHours(2), ['shop' => $shop->id, 'f' => rtrim(strtr(base64_encode($path), '+/', '-_'), '=')]);
    }

    private function shop(Request $request): Shop
    {
        $key = strtoupper(trim((string) ($request->header('X-Licence-Key') ?? '')));
        $shop = $key !== '' ? Shop::where('licence_key', $key)->first() : null;
        if (! $shop || ! $shop->isPaying() || in_array($shop->subscription_status, ['canceled', 'unpaid', 'incomplete_expired'], true)) {
            abort(response()->json(['error' => 'This licence key isn\'t active. Email hello@3dplatebuilder.com if that\'s not right.'], 403));
        }

        return $shop;
    }
}
