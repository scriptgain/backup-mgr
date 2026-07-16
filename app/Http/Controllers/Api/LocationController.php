<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        return Location::query()
            ->withCount('directors')
            ->latest()
            ->paginate(50);
    }

    public function store(Request $request)
    {
        $data = $this->validateLocation($request);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return response()->json(Location::create($data), 201);
    }

    public function show(Location $location)
    {
        return $location->load('directors:id,location_id,name');
    }

    public function update(Request $request, Location $location)
    {
        $location->update($this->validateLocation($request, updating: true, location: $location));

        return $location;
    }

    public function destroy(Location $location)
    {
        $location->delete();

        return response()->noContent();
    }

    private function validateLocation(Request $request, bool $updating = false, ?Location $location = null): array
    {
        $req = $updating ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$req, 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', Rule::unique('locations', 'slug')->ignore($location)],
            'address' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
