<?php

namespace App\Http\Controllers;

use App\Models\Director;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DirectorController extends Controller
{
    public function index()
    {
        $directors = Director::visibleTo(auth()->user())
            ->with('location:id,name')->withCount('hosts')->latest()->get();

        return view('directors.index', compact('directors'));
    }

    /** Abort unless the current user may manage this director. */
    private function guard(Director $director): void
    {
        abort_unless(auth()->user()->isAdmin() || $director->user_id === auth()->id(), 403);
    }

    public function create(Request $request)
    {
        $locations = Location::orderBy('name')->get();
        $selectedLocation = $request->integer('location');

        return view('directors.create', compact('locations', 'selectedLocation'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'location_id' => ['nullable', Rule::exists('locations', 'id')],
            'region' => ['nullable', 'string', 'max:120'],
            'hostname' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['slug'] = Str::slug($data['name']) . '-' . Str::lower(Str::random(4));
        $data['user_id'] = auth()->id();

        $director = Director::create($data);

        return redirect()->route('directors.show', $director)->with('status', "Director \"{$director->name}\" created.");
    }

    public function show(Director $director)
    {
        $this->guard($director);
        $director->load(['location', 'hosts' => fn ($q) => $q->latest(), 'storageDevices']);

        return view('directors.show', compact('director'));
    }

    public function edit(Director $director)
    {
        $this->guard($director);
        $locations = Location::orderBy('name')->get();

        return view('directors.edit', compact('director', 'locations'));
    }

    public function update(Request $request, Director $director)
    {
        $this->guard($director);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'location_id' => ['nullable', Rule::exists('locations', 'id')],
            'hostname' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'notes' => ['nullable', 'string'],
        ]);
        $director->update($data);

        return redirect()->route('directors.show', $director)->with('status', "Director \"{$director->name}\" updated.");
    }

    public function destroy(Director $director)
    {
        $this->guard($director);
        $name = $director->name;
        $director->delete();

        return redirect()->route('directors.index')->with('status', "Director \"{$name}\" deleted.");
    }
}
