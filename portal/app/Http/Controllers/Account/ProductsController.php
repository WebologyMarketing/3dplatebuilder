<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Mail\PortalNotice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/** Extra work from us (one-off add-ons we bill to their card). */
class ProductsController extends Controller
{
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

        return redirect()->to(route('builder').'#extra-work')->with('status', $new ? 'Thanks. We\'ll confirm it, then bill it to your card.' : 'You\'ve already asked for those.');
    }
}
