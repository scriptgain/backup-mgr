<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Host;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HostController extends Controller
{
    private function guard(Host $host): void
    {
        abort_unless($host->isVisibleTo(auth()->user()), 403);
    }

    public function index(Request $request)
    {
        return Host::visibleTo($request->user())
            ->when($request->integer('director_id'), fn ($q, $id) => $q->where('director_id', $id))
            ->with('director:id,name')
            ->latest()
            ->paginate(50);
    }

    public function store(Request $request)
    {
        $data = $this->validateHost($request);
        // A non-admin may only attach the host to a director they own, and may
        // not assign it to another user.
        $director = \App\Models\Director::findOrFail($data['director_id']);
        abort_unless(auth()->user()->isAdmin() || $director->user_id === auth()->id(), 403);
        if (! auth()->user()->isAdmin()) {
            $data['user_id'] = auth()->id();
        }

        return response()->json(Host::create($data), 201);
    }

    public function show(Host $host)
    {
        $this->guard($host);

        return $host->load('director:id,name', 'jobs');
    }

    public function update(Request $request, Host $host)
    {
        $this->guard($host);
        $data = $this->validateHost($request, updating: true);
        // Only admins may reassign ownership.
        if (! auth()->user()->isAdmin()) {
            unset($data['user_id']);
        }
        $host->update($data);

        return $host;
    }

    public function destroy(Host $host)
    {
        $this->guard($host);
        $host->delete();

        return response()->noContent();
    }

    private function validateHost(Request $request, bool $updating = false): array
    {
        $req = $updating ? 'sometimes' : 'required';

        return $request->validate([
            'director_id' => [$req, Rule::exists('directors', 'id')],
            'user_id' => ['nullable', Rule::exists('users', 'id')],
            'name' => [$req, 'string', 'max:120'],
            'connection_type' => ['sometimes', Rule::in(['agent', 'ssh', 'sftp', 'ftp', 'rsync', 's3'])],
            'hostname' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'username' => ['nullable', 'string', 'max:120'],
            'auth_type' => ['nullable', Rule::in(['key', 'password', 'token'])],
            'secret' => ['nullable', 'string'],
            'private_key' => ['nullable', 'string'],
            'disks' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
