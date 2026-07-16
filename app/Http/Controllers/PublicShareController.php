<?php

namespace App\Http\Controllers;

use App\Models\Share;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PublicShareController extends Controller
{
    /** CDN-style public hosting: /s/{slug}/path/to/file */
    public function viaSlug(Request $request, string $slug, string $path = '')
    {
        $share = Share::where('slug', $slug)->where('visibility', 'public')->first();
        abort_if(! $share || $share->isExpired(), 404);

        return $this->serve($request, $share, $path, route('share.slug', ['slug' => $slug]));
    }

    /** Unguessable link (works for private shares): /d/{token}/path */
    public function viaToken(Request $request, string $token, string $path = '')
    {
        $share = Share::where('token', $token)->first();
        abort_if(! $share || $share->isExpired(), 404);

        if ($share->password && ! session()->get('share_unlocked_'.$share->id)) {
            return response()->view('shares.public.unlock', ['share' => $share], 401);
        }

        return $this->serve($request, $share, $path, route('share.token', ['token' => $token]));
    }

    /** Serve a share at its own custom domain's root (invoked by middleware). */
    public function viaDomain(Request $request, Share $share, string $path = '')
    {
        if ($share->password && ! session()->get('share_unlocked_'.$share->id)) {
            return response()->view('shares.public.unlock', ['share' => $share], 401);
        }

        return $this->serve($request, $share, $path, $share->domainUrl() ?? url('/'));
    }

    public function unlock(Request $request, string $token)
    {
        $share = Share::where('token', $token)->firstOrFail();
        $request->validate(['password' => ['required', 'string']]);

        if (! $share->password || ! Hash::check($request->input('password'), $share->password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        session()->put('share_unlocked_'.$share->id, true);

        return redirect()->route('share.token', ['token' => $token]);
    }

    /** Serve a file, a static index, or a directory listing from the share. */
    private function serve(Request $request, Share $share, string $path, string $urlBase)
    {
        $base = realpath($share->absPath());
        abort_if($base === false, 404);

        $rel = trim($path, '/');
        $target = $rel === '' ? $base : realpath($base.'/'.$rel);
        abort_if($target === false || ! (str_starts_with($target, $base.'/') || $target === $base), 404);

        if (is_file($target)) {
            $share->increment('downloads');
            $disposition = $request->boolean('dl') ? 'attachment' : 'inline';

            return response()->file($target, [
                'Content-Disposition' => $disposition.'; filename="'.basename($target).'"',
                'Cache-Control' => 'public, max-age=300',
            ]);
        }

        // Directory: serve index.html for static hosting, else list (if allowed).
        if (is_file($target.'/index.html')) {
            $share->increment('downloads');

            return response()->file($target.'/index.html');
        }

        abort_unless($share->allow_listing, 403, 'Directory listing is disabled for this share.');

        $entries = collect(scandir($target))
            ->reject(fn ($e) => $e === '.' || $e === '..' || $e === 'index.html' && false)
            ->map(function ($e) use ($target, $rel) {
                $full = $target.'/'.$e;

                return (object) [
                    'name' => $e,
                    'rel' => trim($rel.'/'.$e, '/'),
                    'is_dir' => is_dir($full),
                    'size' => is_dir($full) ? null : filesize($full),
                ];
            })
            ->sortBy([['is_dir', 'desc'], ['name', 'asc']])
            ->values();

        return response()->view('shares.public.listing', [
            'share' => $share,
            'entries' => $entries,
            'rel' => $rel,
            'urlBase' => $urlBase,
            'parent' => $rel === '' ? null : trim(dirname($rel) === '.' ? '' : dirname($rel), '/'),
        ]);
    }
}
