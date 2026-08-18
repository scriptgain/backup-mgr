<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class BrandingController extends Controller
{
    /**
     * Every action here changes install-global settings, so all of them are
     * admin-only. Gated once in the constructor rather than per method so a
     * new action cannot be added without the check. (Security fix: these
     * settings pages were reachable by any authenticated non-admin user.)
     */
    public function __construct()
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Admins only.');
    }

    public function edit()
    {
        return view('settings.branding');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'brand_name' => ['required', 'string', 'max:60'],
            'brand_tagline' => ['nullable', 'string', 'max:120'],
            'brand_accent' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        Setting::put('brand_name', $data['brand_name']);
        Setting::put('brand_tagline', $data['brand_tagline'] ?? '');
        Setting::put('brand_accent', strtolower($data['brand_accent']));

        return redirect()->route('settings.branding.edit')->with('status', 'Branding updated.');
    }
}
