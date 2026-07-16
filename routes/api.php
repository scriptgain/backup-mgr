<?php

use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\ApiTokenController;
use App\Http\Controllers\Api\DirectorController;
use App\Http\Controllers\Api\HostController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\RepositoryController;
use App\Http\Controllers\Api\RestoreController;
use App\Http\Controllers\Api\RetentionPolicyController;
use App\Http\Controllers\Api\RunController;
use App\Http\Controllers\Api\ScheduleTemplateController;
use App\Http\Controllers\Api\ShareController;
use App\Http\Controllers\Api\SnapshotController;
use App\Http\Controllers\Api\StorageDeviceController;
use App\Http\Controllers\Api\SyncFolderController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Manager REST API. Bearer token (api_tokens) auth. Base: /api/v1
// Route names are prefixed with "api." so they never collide with the web
// resource route names (directors.*, hosts.*, jobs.*).
Route::prefix('v1')->name('api.')->middleware('api.token')->group(function () {
    Route::get('me', fn (Request $r) => $r->user()->only(['id', 'name', 'email']));

    // Infrastructure.
    Route::apiResource('locations', LocationController::class);
    Route::apiResource('directors', DirectorController::class);
    Route::apiResource('hosts', HostController::class);
    Route::apiResource('storage-devices', StorageDeviceController::class);
    Route::apiResource('repositories', RepositoryController::class);

    // Backups.
    Route::apiResource('retention-policies', RetentionPolicyController::class);
    Route::apiResource('schedule-templates', ScheduleTemplateController::class);
    Route::apiResource('jobs', JobController::class);
    Route::post('jobs/{job}/run', [JobController::class, 'run']);
    Route::apiResource('runs', RunController::class)->only(['index', 'show']);
    Route::apiResource('restores', RestoreController::class)->only(['index', 'show', 'store', 'destroy']);
    Route::get('snapshots', [SnapshotController::class, 'index']);
    Route::get('snapshots/{run}', [SnapshotController::class, 'show']);

    // Sharing.
    Route::apiResource('shares', ShareController::class);
    Route::apiResource('sync-folders', SyncFolderController::class);

    // Access & administration.
    Route::apiResource('users', UserController::class);
    Route::apiResource('api-tokens', ApiTokenController::class)->only(['index', 'store', 'destroy'])->parameters(['api-tokens' => 'apiToken']);
});

// Agent API. Agents dial out to this. Enroll is token-based; the rest use the
// per-host agent key. Base: /api/agent/v1
Route::prefix('agent/v1')->name('agent.')->group(function () {
    Route::post('enroll', [AgentController::class, 'enroll']);
    Route::middleware('agent.auth')->group(function () {
        Route::get('poll', [AgentController::class, 'poll']);
        Route::post('runs/{run}/report', [AgentController::class, 'report']);
        Route::post('runs/{run}/index', [AgentController::class, 'storeIndex']);
        Route::get('restores/poll', [AgentController::class, 'restorePoll']);
        Route::post('restores/{restore}/report', [AgentController::class, 'restoreReport']);
        Route::get('sync/poll', [AgentController::class, 'syncPoll']);
        Route::post('sync/{syncFolder}/report', [AgentController::class, 'syncReport']);
        Route::post('heartbeat', [AgentController::class, 'heartbeat']);
    });
});
