<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Mail\PortalNotice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class ProductsController extends Controller
{
    public function edit(Request $request)
    {
        $shop = $request->user()->shop;

        return view('account.products', [
            'shop' => $shop,
            'catalogue' => config('portal.catalogue'),
            'products' => $shop->products ?? [],
            'features' => config('portal.features'),
            'addons' => config('portal.addons'),
            'requested' => $shop->addonRequests()->whereIn('status', ['requested', 'invoiced'])->pluck('addon')->all(),
        ]);
    }

    public function update(Request $request)
    {
        $c = config('portal.catalogue');
        $data = $request->validate([
            'styles' => ['array'],
            'styles.*' => [Rule::in(array_keys($c['styles']))],
            'price' => ['array'],
            'price.*' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'sizes' => ['array'],
            'sizes.*' => [Rule::in(array_keys($c['sizes']))],
            'extras' => ['array'],
            'extras.*' => [Rule::in(array_keys($c['extras']))],
        ], ['price.*.numeric' => 'Prices need to be numbers, like 34.99.']);

        $styles = [];
        foreach ($data['styles'] ?? [] as $id) {
            $price = $data['price'][$id] ?? null;
            $styles[$id] = $price === null || $price === '' ? null : number_format((float) $price, 2, '.', '');
        }

        $request->user()->shop->update(['products' => [
            'styles' => $styles,
            'sizes' => array_values($data['sizes'] ?? []),
            'extras' => array_values($data['extras'] ?? []),
        ]]);

        return back()->with('status', 'Products saved.');
    }

    public function addons(Request $request)
    {
        $data = $request->validate([
            'addons' => ['required', 'array', 'min:1'],
            'addons.*' => [Rule::in(array_keys(config('portal.addons')))],
        ], ['addons.required' => 'Tick at least one add-on first.']);

        $shop = $request->user()->shop;
        $already = $shop->addonRequests()->whereIn('status', ['requested', 'invoiced'])->pluck('addon')->all();
        $new = array_values(array_diff(array_unique($data['addons']), $already));

        foreach ($new as $key) {
            $shop->addonRequests()->create(['addon' => $key, 'price_pence' => config("portal.addons.$key.price"), 'status' => 'requested']);
        }

        if ($new) {
            $names = collect($new)->map(fn ($k) => config("portal.addons.$k.name"))->implode(', ');
            Mail::to(config('portal.admin_email'))->send(new PortalNotice(
                "Add-ons requested: {$shop->name}",
                ["{$shop->name} has asked for: {$names}."],
                route('admin.payments'),
                'Bill them',
            ));
        }

        return back()->with('status', $new ? 'Thanks. We\'ll confirm it, then bill it to your card.' : 'You\'ve already asked for those.');
    }
}
