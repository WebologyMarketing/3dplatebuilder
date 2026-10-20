<?php

namespace App\Services;

use App\Models\Shop;
use App\Support\SiteConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Publish: freeze the shop's settings for its website, then nudge the website to fetch them.
 * If the nudge doesn't get through, the plugin still checks in every hour.
 */
class Publisher
{
    /** Returns true if the website was told straight away. */
    public function publish(Shop $shop): bool
    {
        $shop->forceFill([
            'published_config' => SiteConfig::build($shop),
            'published_version' => $shop->published_version + 1,
            'published_at' => now(),
        ])->save();

        return $this->ping($shop);
    }

    /** POST {site}/wp-json/pbb/v1/sync, signed with the licence key so only we can trigger it. */
    public function ping(Shop $shop): bool
    {
        $site = $shop->siteUrl();
        if (! $site || ! $shop->licence_key) {
            return false;
        }
        $time = (string) time();
        $version = (string) $shop->published_version;

        try {
            return Http::timeout(8)->acceptJson()->withHeaders([
                'X-PBB-Time' => $time,
                'X-PBB-Version' => $version,
                'X-PBB-Signature' => self::signature($shop->licence_key, $time, $version),
            ])->post(rtrim($site, '/').'/wp-json/pbb/v1/sync')->successful();
        } catch (Throwable $e) {
            Log::info("Publish ping to {$site} failed: ".$e->getMessage());

            return false;
        }
    }

    public static function signature(string $licence, string $time, string $version): string
    {
        return hash_hmac('sha256', $time.'.'.$version, $licence);
    }
}
