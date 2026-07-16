<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use Illuminate\Http\Request;

class ApiTokenController extends Controller
{
    public function index(Request $request)
    {
        return ApiToken::where('user_id', $request->user()->id)->latest()->paginate(50);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        // issue() expects a DateTimeInterface; the validated value is a string.
        $expiresAt = ! empty($data['expires_at']) ? \Illuminate\Support\Carbon::parse($data['expires_at']) : null;
        [$token, $plain] = ApiToken::issue($request->user(), $data['name'], $expiresAt);

        return response()->json([
            'token' => $token,
            'plaintext' => $plain,
        ], 201);
    }

    public function destroy(Request $request, ApiToken $apiToken)
    {
        abort_unless(
            $apiToken->user_id === $request->user()->id || $request->user()->isAdmin(),
            403
        );

        $apiToken->delete();

        return response()->noContent();
    }
}
