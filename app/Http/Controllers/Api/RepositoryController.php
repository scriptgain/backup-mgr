<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Repository;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RepositoryController extends Controller
{
    private function guard(Repository $repository): void
    {
        abort_unless(auth()->user()->isAdmin() || $repository->director?->user_id === auth()->id(), 403);
    }

    public function index()
    {
        $user = auth()->user();

        return Repository::where(fn ($q) => $q->whereNull('director_id')->orWhereHas('director', fn ($d) => $d->visibleTo($user)))
            ->with('director:id,name')->latest()->paginate(50);
    }

    public function store(Request $request)
    {
        $data = $this->validateRepo($request);
        // A non-admin may only create a repository under a director they own.
        if (! empty($data['director_id'])) {
            $director = \App\Models\Director::findOrFail($data['director_id']);
            abort_unless(auth()->user()->isAdmin() || $director->user_id === auth()->id(), 403);
        } else {
            abort_unless(auth()->user()->isAdmin(), 403);
        }

        return response()->json(Repository::create($data), 201);
    }

    public function show(Repository $repository)
    {
        $this->guard($repository);

        return $repository;
    }

    public function update(Request $request, Repository $repository)
    {
        $this->guard($repository);
        $repository->update($this->validateRepo($request, updating: true));

        return $repository;
    }

    public function destroy(Repository $repository)
    {
        $this->guard($repository);
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
