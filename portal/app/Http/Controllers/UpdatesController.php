<?php

namespace App\Http\Controllers;

use App\Models\Release;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * The builder plugin asks here for updates: GET /updates/check?licence=KEY&version=1.10.3&site=https://…
 * A shop with a current subscription gets the latest version and a download link that works for 30 minutes.
 */
class UpdatesController extends Controller
{
    public function check(Request $request)
    {
        $key = strtoupper(trim((string) $request->query('licence')));
        $shop = $key !== '' ? Shop::where('licence_key', $key)->first() : null;

        if (! $shop || ! $shop->isPaying() || in_array($shop->subscription_status, ['canceled', 'unpaid', 'incomplete_expired'], true)) {
            return response()->json(['error' => 'This licence key isn\'t active. Email hello@3dplatebuilder.com if that\'s not right.'], 403);
        }

        $release = Release::latestRelease();
        if (! $release) {
            return response()->json(['version' => null]);
        }

        return response()->json([
            'version' => $release->version,
            'requires' => $release->requires_wp,
            'requires_php' => $release->requires_php,
            'notes' => $release->notes,
            'download_url' => URL::temporarySignedRoute('updates.download', now()->addMinutes(30), ['release' => $release->id, 'licence' => $key]),
        ]);
    }

    public function download(Release $release)
    {
        abort_unless(Storage::disk('local')->exists($release->path), 404);

        return Storage::disk('local')->download($release->path, '3d-plate-builder-'.$release->version.'.zip');
    }
}
