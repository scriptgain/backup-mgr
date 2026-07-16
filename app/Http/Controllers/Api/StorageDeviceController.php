<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StorageDevice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StorageDeviceController extends Controller
{
    public function index(Request $request)
    {
        return StorageDevice::query()
            ->when($request->integer('director_id'), fn ($q, $id) => $q->where('director_id', $id))
            ->with('director:id,name')
            ->latest()
            ->paginate(50);
    }

    public function store(Request $request)
    {
        return response()->json(StorageDevice::create($this->validateStorageDevice($request)), 201);
    }

    public function show(StorageDevice $storageDevice)
    {
        return $storageDevice->load('director:id,name');
    }

    public function update(Request $request, StorageDevice $storageDevice)
    {
        $storageDevice->update($this->validateStorageDevice($request, updating: true));

        return $storageDevice;
    }

    public function destroy(StorageDevice $storageDevice)
    {
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
