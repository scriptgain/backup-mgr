<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Share;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ShareController extends Controller
{
    public function index(Request $request)
    {
        $shares = Share::visibleTo($request->user())->latest()->get();

        return view('shares.index', compact('shares'));
    }

    public function create()
    {
        return view('shares.create', ['share' => new Share(['allow_listing' => true, 'visibility' => 'private'])]);
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

        AuditLog::record('created', 'Share "'.$share->name.'" created', $share);

        return redirect()->route('shares.show', $share)->with('status', 'Share created. Upload files to start hosting.');
    }

    public function show(Request $request, Share $share)
    {
        $this->guard($request, $share);

        $rel = trim((string) $request->query('path', ''), '/');
        $dir = $this->safeDir($share, $rel);

        $entries = collect(File::exists($dir) ? scandir($dir) : [])
            ->reject(fn ($e) => $e === '.' || $e === '..')
            ->map(function ($e) use ($dir, $rel) {
                $full = $dir.'/'.$e;
                return (object) [
                    'name' => $e,
                    'rel' => trim($rel.'/'.$e, '/'),
                    'is_dir' => is_dir($full),
                    'size' => is_dir($full) ? null : filesize($full),
                    'modified' => date('Y-m-d H:i', filemtime($full)),
                ];
            })
            ->sortBy([['is_dir', 'desc'], ['name', 'asc']])
            ->values();

        return view('shares.show', compact('share', 'entries', 'rel'));
    }

    public function edit(Request $request, Share $share)
    {
        $this->guard($request, $share);

        return view('shares.edit', compact('share'));
    }

    public function update(Request $request, Share $share)
    {
        $this->guard($request, $share);
        $data = $this->validated($request, $share);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        } elseif ($request->boolean('remove_password')) {
            $data['password'] = null;
        } else {
            unset($data['password']);
        }

        $share->update($data);
        AuditLog::record('updated', 'Share "'.$share->name.'" updated', $share);

        return redirect()->route('shares.show', $share)->with('status', 'Share updated.');
    }

    public function destroy(Request $request, Share $share)
    {
        $this->guard($request, $share);

        if ($request->boolean('delete_files') && File::isDirectory($share->absPath())) {
            File::deleteDirectory($share->absPath());
        }
        $share->delete();
        AuditLog::record('deleted', 'Share "'.$share->name.'" deleted', $share);

        return redirect()->route('shares.index')->with('status', 'Share deleted.');
    }

    public function upload(Request $request, Share $share)
    {
        $this->guard($request, $share);
        $request->validate([
            'files' => ['required', 'array'],
            'files.*' => ['file', 'max:1048576'], // 1 GB per file
            'path' => ['nullable', 'string'],
        ]);

        $dir = $this->safeDir($share, trim((string) $request->input('path', ''), '/'));
        File::ensureDirectoryExists($dir, 0755);

        $n = 0;
        foreach ($request->file('files') as $file) {
            $name = $this->safeName($file->getClientOriginalName());
            $file->move($dir, $name);
            $n++;
        }

        return back()->with('status', "Uploaded {$n} file(s).");
    }

    public function makeFolder(Request $request, Share $share)
    {
        $this->guard($request, $share);
        $request->validate(['name' => ['required', 'string', 'max:255'], 'path' => ['nullable', 'string']]);

        $dir = $this->safeDir($share, trim((string) $request->input('path', ''), '/'));
        File::ensureDirectoryExists($dir.'/'.$this->safeName($request->input('name')), 0755);

        return back()->with('status', 'Folder created.');
    }

    public function deleteFile(Request $request, Share $share)
    {
        $this->guard($request, $share);
        $request->validate(['rel' => ['required', 'string']]);

        $target = $this->safePath($share, trim($request->input('rel'), '/'));
        if ($target && File::exists($target)) {
            File::isDirectory($target) ? File::deleteDirectory($target) : File::delete($target);
        }

        return back()->with('status', 'Deleted.');
    }

    private function validated(Request $request, ?Share $share = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9][a-z0-9-]*$/', Rule::unique('shares', 'slug')->ignore($share)],
            'custom_domain' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9.-]+\.[a-z]{2,}$/i', Rule::unique('shares', 'custom_domain')->ignore($share)],
            'visibility' => ['required', Rule::in(['public', 'private'])],
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

        $data['allow_uploads'] = $request->boolean('allow_uploads');
        $data['allow_listing'] = $request->boolean('allow_listing');
        $data['custom_domain'] = ! empty($data['custom_domain']) ? strtolower(trim($data['custom_domain'])) : null;
        unset($data['password']); // handled explicitly by the caller

        return $data;
    }

    /** Resolve a sub-directory within the share, rejecting traversal. */
    private function safeDir(Share $share, string $rel): string
    {
        $base = realpath($share->absPath());
        if ($base === false) {
            File::ensureDirectoryExists($share->absPath(), 0755);
            $base = realpath($share->absPath());
        }
        if ($rel === '') {
            return $base;
        }
        $candidate = $base.'/'.$rel;
        $real = realpath($candidate);
        // New dirs won't resolve yet; validate the normalized path stays inside.
        $check = $real !== false ? $real : $this->normalize($candidate);
        abort_unless(str_starts_with($check, $base.'/') || $check === $base, 404);

        return $real !== false ? $real : $candidate;
    }

    /** Resolve an existing file/dir within the share, rejecting traversal. */
    private function safePath(Share $share, string $rel): ?string
    {
        $base = realpath($share->absPath());
        $real = realpath($base.'/'.$rel);
        if ($base === false || $real === false) {
            return null;
        }
        return str_starts_with($real, $base.'/') ? $real : null;
    }

    private function normalize(string $path): string
    {
        $parts = [];
        foreach (explode('/', $path) as $seg) {
            if ($seg === '' || $seg === '.') {
                continue;
            }
            if ($seg === '..') {
                array_pop($parts);
            } else {
                $parts[] = $seg;
            }
        }
        return '/'.implode('/', $parts);
    }

    private function safeName(string $name): string
    {
        return trim(str_replace(['/', '\\', "\0"], '', basename($name))) ?: 'file';
    }

    private function guard(Request $request, Share $share): void
    {
        abort_unless($request->user()->isAdmin() || $share->user_id === $request->user()->id, 403);
    }
}
