<?php

namespace App\Http\Controllers;

use App\Models\Director;
use App\Models\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RepositoryController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $repositories = Repository::where(fn ($q) => $q->whereNull('director_id')->orWhereHas('director', fn ($d) => $d->visibleTo($user)))
            ->with('director:id,name')->latest()->get();

        return view('repositories.index', compact('repositories'));
    }

    private function guard(Repository $repository): void
    {
        if ($repository->director_id) {
            abort_unless(auth()->user()->isAdmin() || $repository->director?->user_id === auth()->id(), 403);
        }
    }

    public function create()
    {
        $directors = Director::visibleTo(auth()->user())->orderBy('name')->get();

        return view('repositories.create', compact('directors'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Repository::create($this->pack($data));

        return redirect()->route('repositories.index')->with('status', "Repository \"{$data['name']}\" created.");
    }

    public function show(Repository $repository)
    {
        $this->guard($repository);

        // Snapshots stored in this repository = runs of its jobs that produced a
        // restore point. Deleted hosts/jobs/snapshots cascade out, so this list
        // reflects exactly what remains.
        $snapshots = \App\Models\Run::whereNotNull('snapshot_id')
            ->whereHas('job', fn ($q) => $q->where('repository_id', $repository->id))
            ->with('job.host')
            ->latest()
            ->limit(200)
            ->get();

        return view('repositories.show', compact('repository', 'snapshots'));
    }

    public function edit(Repository $repository)
    {
        $this->guard($repository);
        $directors = Director::visibleTo(auth()->user())->orderBy('name')->get();

        return view('repositories.edit', compact('repository', 'directors'));
    }

    public function update(Request $request, Repository $repository)
    {
        $this->guard($repository);
        $data = $this->validated($request, updating: true);
        $repository->update($this->pack($data, $repository));

        return redirect()->route('repositories.show', $repository)->with('status', "Repository \"{$repository->name}\" updated.");
    }

    public function destroy(Repository $repository)
    {
        $this->guard($repository);
        $name = $repository->name;
        $repository->delete();

        return redirect()->route('repositories.index')->with('status', "Repository \"{$name}\" deleted.");
    }

    private function validated(Request $request, bool $updating = false): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'director_id' => ['nullable', Rule::exists('directors', 'id')],
            'backend' => ['required', Rule::in(['s3', 'filesystem'])],
            'compression' => ['required', Rule::in(['zstd', 'gzip', 's2', 'none'])],
            // s3
            'endpoint' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:120'],
            'bucket' => ['nullable', 'string', 'max:255'],
            'prefix' => ['nullable', 'string', 'max:255'],
            'access_key_id' => ['nullable', 'string', 'max:255'],
            'secret_access_key' => ['nullable', 'string'],
            // filesystem
            'path' => ['nullable', 'string', 'max:1024'],
            // kopia repo password — optional; auto-generated if left blank on create.
            'password' => ['nullable', 'string', 'min:8'],
        ]);
    }

    /** Fold backend-specific fields into the config JSON column. */
    private function pack(array $data, ?Repository $existing = null): array
    {
        $config = $data['backend'] === 's3'
            ? array_filter([
                'endpoint' => $data['endpoint'] ?? null,
                'region' => $data['region'] ?? null,
                'bucket' => $data['bucket'] ?? null,
                'prefix' => $data['prefix'] ?? null,
            ], fn ($v) => $v !== null && $v !== '')
            : ['path' => $data['path'] ?? null];

        return [
            'name' => $data['name'],
            'director_id' => $data['director_id'] ?? null,
            'backend' => $data['backend'],
            'compression' => $data['compression'],
            'config' => $config,
            // Preserve stored secrets when left blank on edit.
            'access_key_id' => ($data['access_key_id'] ?? null) ?: $existing?->access_key_id,
            'secret_access_key' => ($data['secret_access_key'] ?? null) ?: $existing?->secret_access_key,
            // Auto-generate a strong repository password when none is provided.
            'password' => ($data['password'] ?? null) ?: ($existing?->password ?: Str::random(40)),
            'status' => 'active',
        ];
    }
}
