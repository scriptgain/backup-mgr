<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StorageDevice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StorageDeviceController extends Controller
{
    private function guard(StorageDevice $storageDevice): void
    {
        abort_unless(auth()->user()->isAdmin() || $storageDevice->director?->user_id === auth()->id(), 403);
    }

    public function index(Request $request)
    {
        return StorageDevice::whereHas('director', fn ($q) => $q->visibleTo(auth()->user()))
            ->when($request->integer('director_id'), fn ($q, $id) => $q->where('director_id', $id))
            ->with('director:id,name')
            ->latest()
            ->paginate(50);
    }

    public function store(Request $request)
    {
        $data = $this->validateStorageDevice($request);
        $director = \App\Models\Director::findOrFail($data['director_id']);
        abort_unless(auth()->user()->isAdmin() || $director->user_id === auth()->id(), 403);

        return response()->json(StorageDevice::create($data), 201);
    }

    public function show(StorageDevice $storageDevice)
    {
        $this->guard($storageDevice);

        return $storageDevice->load('director:id,name');
    }

    public function update(Request $request, StorageDevice $storageDevice)
    {
        $this->guard($storageDevice);
        $storageDevice->update($this->validateStorageDevice($request, updating: true));

        return $storageDevice;
    }

    public function destroy(StorageDevice $storageDevice)
    {
        $this->guard($storageDevice);
        $storageDevice->delete();

        return response()->noContent();
    }

    private function validateStorageDevice(Request $request, bool $updating = false): array
    {
        $req = $updating ? 'sometimes' : 'required';

        return $request->validate([
            'director_id' => [$req, Rule::exists('directors', 'id')],
            'name' => [$req, 'string', 'max:120'],
            'mount_path' => [$req, 'string', 'max:1024'],
            'total_bytes' => ['nullable', 'integer', 'min:0'],
            'used_bytes' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
