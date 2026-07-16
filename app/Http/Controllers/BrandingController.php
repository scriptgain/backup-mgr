<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class BrandingController extends Controller
{
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
