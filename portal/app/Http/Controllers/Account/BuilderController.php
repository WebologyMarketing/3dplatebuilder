<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Support\BuilderSettings;
use Illuminate\Http\Request;

/** "Your builder": what's switched on in their plate builder and what it costs, plus their own add-ons. */
class BuilderController extends Controller
{
    public function edit(Request $request)
    {
        $shop = $request->user()->shop;

        return view('account.builder', [
            'shop' => $shop,
            'c' => config('portal.catalogue'),
            'b' => BuilderSettings::forShop($shop),
            'addons' => config('portal.addons'),
            'requested' => $shop->addonRequests()->whereIn('status', ['requested', 'invoiced'])->pluck('addon')->all(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(BuilderSettings::rules(), [
            '*.*.pair.numeric' => 'Prices need to be numbers, like 34.99.',
            '*.*.single.numeric' => 'Prices need to be numbers, like 19.99.',
            '*.*.price.numeric' => 'Prices need to be numbers, like 10.00.',
            'fitting.numeric' => 'The fitting price needs to be a number, like 30.00.',
            'custom.max' => 'You can have up to :max of your own add-ons.',
        ]);

        $shop = $request->user()->shop;
        $shop->update(['builder' => BuilderSettings::fromInput($data, BuilderSettings::forShop($shop))]);

        return redirect()->route('builder')->with('status', $shop->published_version
            ? 'Saved. Publish when you\'re ready for it to show on your website.'
            : 'Saved.');
    }
}
