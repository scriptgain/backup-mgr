<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Repository;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RepositoryController extends Controller
{
    public function index()
    {
        return Repository::with('director:id,name')->latest()->paginate(50);
    }

    public function store(Request $request)
    {
        return response()->json(Repository::create($this->validateRepo($request)), 201);
    }

    public function show(Repository $repository)
    {
        return $repository;
    }

    public function update(Request $request, Repository $repository)
    {
        $repository->update($this->validateRepo($request, updating: true));

        return $repository;
    }

    public function destroy(Repository $repository)
    {
        $repository->delete();

        return response()->noContent();
    }

    private function validateRepo(Request $request, bool $updating = false): array
    {
        $req = $updating ? 'sometimes' : 'required';

        return $request->validate([
            'director_id' => ['nullable', Rule::exists('directors', 'id')],
            'name' => [$req, 'string', 'max:120'],
            'backend' => ['sometimes', Rule::in(['s3', 'filesystem', 'sftp'])],
            'config' => ['nullable', 'array'],
            'access_key_id' => ['nullable', 'string'],
            'secret_access_key' => ['nullable', 'string'],
            'password' => ['nullable', 'string'],
            'compression' => ['sometimes', Rule::in(['zstd', 'gzip', 's2', 'none'])],
            'status' => ['sometimes', 'string'],
        ]);
    }
}
