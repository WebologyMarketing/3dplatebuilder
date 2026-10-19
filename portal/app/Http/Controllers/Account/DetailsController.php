<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Mail\PortalNotice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DetailsController extends Controller
{
    public function edit(Request $request)
    {
        $shop = $request->user()->shop;

        return view('account.details', [
            'shop' => $shop,
            'fonts' => config('portal.fonts'),
            'rnps' => $shop->documents()->where('type', 'rnps')->latest()->first(),
        ]);
    }

    public function update(Request $request)
    {
        $hex = ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'];
        $data = $request->validate([
            'legal_name' => ['nullable', 'string', 'max:160'],
            'trading_name' => ['nullable', 'string', 'max:160'],
            'company_number' => ['nullable', 'string', 'max:20'],
            'vat_number' => ['nullable', 'string', 'max:20'],
            'registered_address' => ['nullable', 'string', 'max:500'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'domain' => ['nullable', 'string', 'max:190', 'regex:/^(?!https?:)[a-z0-9.-]+\.[a-z]{2,}$/i'],
            'supplier_name' => ['nullable', 'string', 'max:60'],
            'supplier_postcode' => ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z]{1,2}\d[A-Za-z\d]?\s*\d[A-Za-z]{2}$/'],
            'rnps_number' => ['nullable', 'string', 'max:30'],
            'collection_address' => ['nullable', 'string', 'max:500'],
            'accent_colour' => $hex,
            'dark_colour' => $hex,
            'heading_font' => ['nullable', Rule::in(config('portal.fonts'))],
        ], [
            'domain.regex' => 'Just the domain please, like yourplates.co.uk.',
            'supplier_postcode.regex' => 'That doesn\'t look like a UK postcode.',
            'accent_colour.regex' => 'Use a colour code like #E8452C.',
            'dark_colour.regex' => 'Use a colour code like #16191C.',
        ]);

        if (! empty($data['supplier_postcode'])) {
            $data['supplier_postcode'] = strtoupper(preg_replace('/\s+/', ' ', trim($data['supplier_postcode'])));
        }
        if (! empty($data['domain'])) {
            $data['domain'] = strtolower(preg_replace('/^www\./i', '', $data['domain']));
        }

        $request->user()->shop->update($data);

        return back()->with('status', 'Saved.');
    }

    public function logo(Request $request)
    {
        $request->validate([
            'logo' => ['required_without:logo_white', 'nullable', 'file', 'mimes:svg,png', 'max:5120'],
            'logo_white' => ['nullable', 'file', 'mimes:svg,png', 'max:5120'],
        ], ['*.mimes' => 'Please upload an SVG or PNG file.']);

        $shop = $request->user()->shop;
        foreach (['logo' => 'logo_path', 'logo_white' => 'logo_white_path'] as $field => $column) {
            if ($file = $request->file($field)) {
                if ($shop->$column) {
                    Storage::disk('local')->delete($shop->$column);
                }
                $shop->$column = $file->store("shops/{$shop->id}/brand", 'local');
            }
        }
        $shop->save();

        return redirect()->to(route('details').'#logo')->with('status', 'Logo uploaded.');
    }

    public function fonts(Request $request)
    {
        $request->validate([
            'fonts' => ['required', 'array', 'max:4'],
            'fonts.*' => ['file', 'max:4096', function ($attr, $file, $fail) {
                if (! in_array(strtolower($file->getClientOriginalExtension()), ['woff2', 'woff', 'ttf', 'otf'], true)) {
                    $fail('Fonts need to be WOFF2, WOFF, TTF or OTF files.');
                }
            }],
        ]);

        $shop = $request->user()->shop;
        $saved = $shop->font_files ?? [];
        foreach ($request->file('fonts') as $file) {
            $saved[] = ['path' => $file->store("shops/{$shop->id}/fonts", 'local'), 'name' => $file->getClientOriginalName()];
        }
        $shop->update(['font_files' => array_slice($saved, -8)]);

        return redirect()->to(route('details').'#brand')->with('status', 'Font files uploaded.');
    }

    public function document(Request $request)
    {
        $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], ['document.mimes' => 'Please upload a PDF or a photo (JPG or PNG).']);

        $shop = $request->user()->shop;
        $file = $request->file('document');
        $shop->documents()->create([
            'type' => 'rnps',
            'path' => $file->store("shops/{$shop->id}/documents", 'local'),
            'original_name' => mb_strimwidth($file->getClientOriginalName(), 0, 190),
            'status' => 'pending',
        ]);

        Mail::to(config('portal.admin_email'))->send(new PortalNotice(
            "Document to check: {$shop->name}",
            ["{$shop->name} has uploaded their RNPS registration for you to check."],
            route('admin.documents'),
            'Check it',
        ));

        return redirect()->to(route('details').'#docs')->with('status', 'Uploaded. We\'ll check it and let you know.');
    }

    /** The shop's own uploads, for previews on this page. */
    public function file(Request $request, string $kind)
    {
        $shop = $request->user()->shop;
        $path = match ($kind) {
            'logo' => $shop->logo_path,
            'logo-white' => $shop->logo_white_path,
            default => null,
        };
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return self::safeFile($path);
    }

    /** Serves an upload with headers that stop an SVG's scripts from running if it's opened directly. */
    public static function safeFile(string $path, bool $download = false)
    {
        $response = $download
            ? Storage::disk('local')->download($path)
            : Storage::disk('local')->response($path);
        $response->headers->set('Content-Security-Policy', "default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; sandbox");
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
