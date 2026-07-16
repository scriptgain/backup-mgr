<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Share;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ShareController extends Controller
{
    public function index(Request $request)
    {
        return Share::query()
            ->when($request->integer('host_id'), fn ($q, $id) => $q->where('host_id', $id))
            ->with('host:id,name')
            ->latest()
            ->paginate(50);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $share = new Share($data);
        $share->user_id = $request->user()->id;
        $share->password = $request->filled('password') ? Hash::make($request->input('password')) : null;
        $share->save(); // slug, token, and path are generated in booted()

        // Create the backing folder.
        File::ensureDirectoryExists($share->path, 0755);

        return response()->json($share, 201);
    }

    public function show(Share $share)
    {
        return $share->load('host:id,name');
    }

    public function update(Request $request, Share $share)
    {
        $data = $this->validated($request, $share, updating: true);

        // Never allow the generated slug to be overwritten from input.
        unset($data['slug']);

        if ($request->has('password')) {
            $share->password = $request->filled('password')
                ? Hash::make($request->input('password'))
                : null;
        }

        $share->fill($data)->save();

        return $share->load('host:id,name');
    }

    public function destroy(Share $share)
    {
        if (File::isDirectory($share->absPath())) {
            File::deleteDirectory($share->absPath());
        }

        $share->delete();

        return response()->noContent();
    }

    /**
     * Validate a share payload, mirroring the web ShareController rules.
     *
     * Returns the validated data with the password stripped; the caller
     * handles password hashing explicitly. Never accepts user_id, token,
     * path, or downloads from input.
     */
    private function validated(Request $request, ?Share $share = null, bool $updating = false): array
    {
        $req = $updating ? 'sometimes' : 'required';

        $data = $request->validate([
            'name' => [$req, 'string', 'max:120'],
            'slug' =>['nullable', 'string', 'max:60', 'regex:/^[a-z0-9][a-z0-9-]*$/', Rule::unique('shares', 'slug')->ignore($share)],
            'custom_domain' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9.-]+\.[a-z]{2,}$/i', Rule::unique('shares', 'custom_domain')->ignore($share)],
            'visibility' => [$req, Rule::in(['public', 'private'])],
            'allow_uploads' => ['sometimes', 'boolean'],
            'allow_listing' => ['sometimes', 'boolean'],
            'expires_at' => ['nullable', 'date'],
            'password' => ['nullable', 'string', 'min:4', 'max:100'],
        ], [
            'slug.regex' => 'The URL may use lowercase letters, numbers, and hyphens only.',
            'slug.unique' => 'That URL is already taken.',
            'custom_domain.regex' => 'Enter a bare domain like cdn.example.com (no https:// or path).',
            'custom_domain.unique' => 'That domain is already in use by another share.',
        ]);

        if (array_key_exists('custom_domain', $data)) {
            $data['custom_domain'] = ! empty($data['custom_domain']) ? strtolower(trim($data['custom_domain'])) : null;
        }

        unset($data['password']); // handled explicitly by the caller

        return $data;
    }
}
