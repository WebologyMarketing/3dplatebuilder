<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Release;
use App\Models\Shop;
use Illuminate\Http\Request;

class ReleaseController extends Controller
{
    public function index()
    {
        return view('admin.releases', [
            'releases' => Release::all()->sort(fn ($a, $b) => version_compare($b->version, $a->version)),
            'licensed' => Shop::whereNotNull('licence_key')->get()->filter->isPaying()->count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'version' => ['required', 'regex:/^\d+\.\d+\.\d+$/', 'unique:releases,version'],
            'zip' => ['required', 'file', 'mimes:zip', 'max:51200'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'requires_wp' => ['nullable', 'string', 'max:10'],
            'requires_php' => ['nullable', 'string', 'max:10'],
        ], ['version.regex' => 'Use a version like 1.10.4.']);

        $path = $request->file('zip')->storeAs('releases', '3d-plate-builder-'.$data['version'].'.zip', 'local');
        Release::create(['version' => $data['version'], 'path' => $path, 'notes' => $data['notes'] ?? null,
            'requires_wp' => $data['requires_wp'] ?? null, 'requires_php' => $data['requires_php'] ?? null]);

        return back()->with('status', "Version {$data['version']} published. Shops will see it on their next update check.");
    }
}
