<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Account\DetailsController;
use App\Http\Controllers\Controller;
use App\Mail\PortalNotice;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('show', 'all');
        $q = trim((string) $request->query('q'));

        $shops = Shop::with('users')->latest()->get()
            ->filter(fn (Shop $s) => match ($filter) {
                'live' => $s->stage === 'live',
                'setup' => in_array($s->stage, ['not_paid', 'onboarding', 'in_build', 'review'], true),
                'overdue' => $s->overdueBalancePence() > 0,
                'cancelled' => $s->stage === 'cancelled',
                default => true,
            })
            ->filter(fn (Shop $s) => $q === '' || str_contains(strtolower($s->name.' '.$s->domain.' '.$s->users->pluck('email')->implode(' ')), strtolower($q)));

        return view('admin.shops', compact('shops', 'filter', 'q'));
    }

    public function show(Shop $shop)
    {
        return view('admin.shop', [
            'shop' => $shop->load('users', 'documents', 'addonRequests'),
            'steps' => $shop->onboardingSteps(),
            'tickets' => $shop->tickets()->latest('updated_at')->get(),
            'payments' => $shop->payments()->whereNotIn('status', ['draft'])->latest('id')->get(),
            'catalogue' => config('portal.catalogue'),
        ]);
    }

    public function update(Request $request, Shop $shop)
    {
        $data = $request->validate([
            'stage' => ['required', Rule::in(array_keys(Shop::STAGES))],
            'preview_url' => ['nullable', 'url', 'max:255'],
            'live_url' => ['nullable', 'url', 'max:255'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $before = $shop->stage;
        $shop->update($data);

        if ($before !== $shop->stage && in_array($shop->stage, ['review', 'live'], true)) {
            $lines = $shop->stage === 'review'
                ? ['Your shop is ready for you to check.'.($shop->preview_url ? ' Have a look at your preview link and tell us anything you\'d like changed.' : '')]
                : ['Your shop is live and ready to take orders.'];
            foreach ($shop->users as $user) {
                Mail::to($user->email)->send(new PortalNotice(
                    $shop->stage === 'review' ? 'Your shop is ready to check' : 'Your shop is live',
                    $lines,
                    $shop->stage === 'review' ? ($shop->preview_url ?: route('dashboard')) : ($shop->live_url ?: route('dashboard')),
                    $shop->stage === 'review' ? 'Open your preview' : 'Visit your shop',
                ));
            }
        }

        return back()->with('status', 'Saved.');
    }

    public function licence(Shop $shop)
    {
        $shop->update(['licence_key' => Shop::newLicenceKey()]);

        return back()->with('status', 'New licence key made. The old one stops working for updates straight away.');
    }

    public function file(Shop $shop, string $kind)
    {
        $path = match (true) {
            $kind === 'logo' => $shop->logo_path,
            $kind === 'logo-white' => $shop->logo_white_path,
            str_starts_with($kind, 'font-') => $shop->font_files[(int) substr($kind, 5)]['path'] ?? null,
            default => null,
        };
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return DetailsController::safeFile($path, str_starts_with($kind, 'font-') || request()->boolean('download'));
    }

    /** Everything needed to build the shop, as JSON, ready for the shop template. */
    public function export(Shop $shop)
    {
        $c = config('portal.catalogue');
        $p = $shop->products ?? [];

        return response()->json([
            'shop' => $shop->only(['name', 'plan', 'legal_name', 'trading_name', 'company_number', 'vat_number', 'registered_address',
                'contact_email', 'domain', 'supplier_name', 'supplier_postcode', 'rnps_number', 'collection_address',
                'accent_colour', 'dark_colour', 'heading_font', 'licence_key']),
            'products' => [
                'styles' => collect($p['styles'] ?? [])->map(fn ($price, $id) => ['id' => $id, 'label' => $c['styles'][$id] ?? $id, 'pair_price' => $price])->values(),
                'sizes' => $p['sizes'] ?? [],
                'extras' => $p['extras'] ?? [],
            ],
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ->header('Content-Disposition', 'attachment; filename="shop-'.$shop->id.'.json"');
    }
}
