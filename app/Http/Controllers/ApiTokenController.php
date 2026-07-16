<?php

namespace App\Http\Controllers;

use App\Models\ApiToken;
use Illuminate\Http\Request;

class ApiTokenController extends Controller
{
    public function index(Request $request)
    {
        $tokens = ApiToken::where('user_id', $request->user()->id)->latest()->get();

        return view('settings.tokens', compact('tokens'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        [$token, $plain] = ApiToken::issue($request->user(), $data['name']);

        return redirect()->route('settings.tokens.index')
            ->with('token_plain', $plain)
            ->with('status', 'Token created. Copy it now — it is shown only once.');
    }

    public function destroy(Request $request, ApiToken $apiToken)
    {
        abort_unless($apiToken->user_id === $request->user()->id, 403);
        $apiToken->delete();

        return redirect()->route('settings.tokens.index')->with('status', 'Token revoked.');
    }
}
