<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RetentionPolicy;
use Illuminate\Http\Request;

class RetentionPolicyController extends Controller
{
    public function index()
    {
        return RetentionPolicy::latest()->paginate(50);
    }

    public function store(Request $request)
    {
        return response()->json(RetentionPolicy::create($this->validatePolicy($request)), 201);
    }

    public function show(RetentionPolicy $retentionPolicy)
    {
        return $retentionPolicy;
    }

    public function update(Request $request, RetentionPolicy $retentionPolicy)
    {
        $retentionPolicy->update($this->validatePolicy($request, updating: true));

        return $retentionPolicy;
    }

    public function destroy(RetentionPolicy $retentionPolicy)
    {
        $retentionPolicy->delete();

        return response()->noContent();
    }

    private function validatePolicy(Request $request, bool $updating = false): array
    {
        $req = $updating ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$req, 'string', 'max:120'],
            'keep_latest' => ['integer', 'min:0'],
            'keep_hourly' => ['integer', 'min:0'],
            'keep_daily' => ['integer', 'min:0'],
            'keep_weekly' => ['integer', 'min:0'],
            'keep_monthly' => ['integer', 'min:0'],
            'keep_annual' => ['integer', 'min:0'],
        ]);
    }
}
