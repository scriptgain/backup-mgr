<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Director;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DirectorController extends Controller
{
    public function index()
    {
        return Director::withCount('hosts')->latest()->paginate(50);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'unique:directors,slug'],
            'region' => ['nullable', 'string', 'max:120'],
            'is_local' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']) . '-' . Str::lower(Str::random(4));

        return response()->json(Director::create($data), 201);
    }

    public function show(Director $director)
    {
        return $director->load('hosts', 'repositories');
    }

    public function update(Request $request, Director $director)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:120'],
            'status' => ['sometimes', 'string'],
            'notes' => ['nullable', 'string'],
        ]);
        $director->update($data);

        return $director;
    }

    public function destroy(Director $director)
    {
        $director->delete();

        return response()->noContent();
    }
}
