<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Host;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HostController extends Controller
{
    public function index(Request $request)
    {
        return Host::query()
            ->when($request->integer('director_id'), fn ($q, $id) => $q->where('director_id', $id))
            ->with('director:id,name')
            ->latest()
            ->paginate(50);
    }

    public function store(Request $request)
    {
        $data = $this->validateHost($request);

        return response()->json(Host::create($data), 201);
    }

    public function show(Host $host)
    {
        return $host->load('director:id,name', 'jobs');
    }

    public function update(Request $request, Host $host)
    {
        $host->update($this->validateHost($request, updating: true));

        return $host;
    }

    public function destroy(Host $host)
    {
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
