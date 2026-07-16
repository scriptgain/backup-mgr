<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LocationController extends Controller
{
    public function index()
    {
        $locations = Location::withCount('directors')->latest()->get();

        return view('locations.index', compact('locations'));
    }

    public function create()
    {
        return view('locations.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['slug'] = Str::slug($data['name']) . '-' . Str::lower(Str::random(4));

        $location = Location::create($data);

        return redirect()->route('locations.show', $location)->with('status', "Location \"{$location->name}\" created.");
    }

    public function show(Location $location)
    {
        $location->load(['directors' => fn ($q) => $q->withCount('hosts')->latest()]);

        return view('locations.show', compact('location'));
    }

    public function edit(Location $location)
    {
        return view('locations.edit', compact('location'));
    }

    public function update(Request $request, Location $location)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
        ]);
        $location->update($data);

        return redirect()->route('locations.show', $location)->with('status', "Location \"{$location->name}\" updated.");
    }

    public function destroy(Location $location)
    {
        $name = $location->name;
        $location->delete();

        return redirect()->route('locations.index')->with('status', "Location \"{$name}\" deleted.");
    }
}
