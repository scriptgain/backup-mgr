<?php

use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FaviconController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\DirectorController;
use App\Http\Controllers\HostController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RepositoryController;
use App\Http\Controllers\RestoreController;
use App\Http\Controllers\RunController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\PublicShareController;
use App\Http\Controllers\ScheduleTemplateController;
use App\Http\Controllers\SnapshotController;
use App\Http\Controllers\StorageDeviceController;
use App\Http\Controllers\SyncFolderController;
use App\Http\Controllers\FirewallController;
use App\Http\Controllers\HostSslController;
use App\Http\Controllers\GeneralSettingsController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// First-run setup wizard. Not behind 'auth': step 1 (create admin) runs as a
// guest, step 2 (license) runs authed. Access is governed by EnsureSetup.
Route::prefix('setup')->group(function () {
    Route::get('/', [SetupController::class, 'index'])->name('setup.index');
    Route::post('/admin', [SetupController::class, 'storeAdmin'])->name('setup.admin');
    Route::post('/license', [SetupController::class, 'storeLicense'])->name('setup.license');
});

// Guest (unauthenticated) routes.
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});

// One-click signed magic-login link (short-lived; the signature is the credential).
Route::get('/magic/{user}', [AuthController::class, 'magic'])->name('magic-login')->middleware('signed');

// Two-factor challenge (after password, before full login — user not yet authed).
Route::get('/2fa', [AuthController::class, 'challenge'])->name('2fa.challenge');
Route::post('/2fa', [AuthController::class, 'challengeVerify'])->middleware('throttle:10,1');

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Brand favicon, accent-tinted from DB-driven branding (public — browsers fetch it pre-login).
// Extension-less on purpose: CloudPanel nginx serves *.svg/*.png as static files
// and 404s before reaching PHP. The <link type="…"> attribute sets the MIME.
Route::get('/brand/favicon', [FaviconController::class, 'svg'])->name('favicon.svg');
Route::get('/brand/favicon-png', [FaviconController::class, 'faviconPng'])->name('favicon.png');
Route::get('/brand/favicon-apple', [FaviconController::class, 'appleIcon'])->name('favicon.apple');

// Public file shares / CDN-style hosting (no auth). {path} may contain slashes.
Route::get('/s/{slug}/{path?}', [PublicShareController::class, 'viaSlug'])->where('path', '.*')->name('share.slug');
Route::post('/d/{token}/unlock', [PublicShareController::class, 'unlock'])->name('share.unlock');
Route::get('/d/{token}/{path?}', [PublicShareController::class, 'viaToken'])->where('path', '.*')->name('share.token');

// One control panel for the whole fleet, behind auth.
Route::middleware(['auth', 'security.policy'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    // Design galleries (ScriptGain navbar/footer pickers) — self-contained pages.
    Route::view('/ui/navbars', 'ui.navbars')->name('ui.navbars');
    Route::view('/ui/footers', 'ui.footers')->name('ui.footers');

    // Manager -> Locations -> Directors -> Hosts
    Route::resource('locations', LocationController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::resource('directors', DirectorController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::get('directors/{director}/hosts/create', [HostController::class, 'create'])->name('hosts.create');
    Route::post('directors/{director}/hosts', [HostController::class, 'store'])->name('hosts.store');
    Route::post('directors/{director}/storage', [StorageDeviceController::class, 'store'])->name('directors.storage.store');
    Route::post('directors/{director}/storage/detect', [StorageDeviceController::class, 'detect'])->name('directors.storage.detect');
    Route::delete('storage/{storageDevice}', [StorageDeviceController::class, 'destroy'])->name('storage.destroy');
    Route::get('hosts', [HostController::class, 'index'])->name('hosts.index');
    Route::get('hosts/{host}/edit', [HostController::class, 'edit'])->name('hosts.edit');
    Route::get('hosts/{host}/browse', [HostController::class, 'browse'])->name('hosts.browse');
    Route::post('hosts/{host}/mkdir', [HostController::class, 'makeDir'])->name('hosts.mkdir');
    Route::get('hosts/{host}', [HostController::class, 'show'])->name('hosts.show');
    Route::put('hosts/{host}', [HostController::class, 'update'])->name('hosts.update');
    Route::post('hosts/{host}/backup', [HostController::class, 'backup'])->name('hosts.backup');
    Route::post('hosts/{host}/quick-backup', [HostController::class, 'quickBackup'])->name('hosts.quickBackup');
    Route::post('hosts/{host}/test-connection', [HostController::class, 'testConnection'])->name('hosts.test');
    Route::post('hosts/{host}/enroll', [HostController::class, 'enroll'])->name('hosts.enroll');
    Route::delete('hosts/{host}', [HostController::class, 'destroy'])->name('hosts.destroy');

    // File Shares / hosting (local-disk buckets served publicly)
    Route::resource('shares', ShareController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::post('shares/{share}/upload', [ShareController::class, 'upload'])->name('shares.upload');
    Route::post('shares/{share}/folder', [ShareController::class, 'makeFolder'])->name('shares.folder');
    Route::delete('shares/{share}/file', [ShareController::class, 'deleteFile'])->name('shares.delete-file');

    // Repositories + Jobs
    Route::resource('repositories', RepositoryController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::resource('jobs', JobController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::post('jobs/{job}/run', [JobController::class, 'run'])->name('jobs.run');
    Route::get('runs/{run}', [RunController::class, 'show'])->name('runs.show');
    Route::delete('runs/{run}', [RunController::class, 'destroy'])->name('runs.destroy');

    // Schedule templates
    Route::get('schedule-templates', [ScheduleTemplateController::class, 'index'])->name('schedule-templates.index');
    Route::post('schedule-templates', [ScheduleTemplateController::class, 'store'])->name('schedule-templates.store');
    Route::delete('schedule-templates/{scheduleTemplate}', [ScheduleTemplateController::class, 'destroy'])->name('schedule-templates.destroy');

    Route::view('/settings', 'settings.index')->name('settings.index');
    Route::get('settings/tokens', [ApiTokenController::class, 'index'])->name('settings.tokens.index');
    Route::post('settings/tokens', [ApiTokenController::class, 'store'])->name('settings.tokens.store');
    Route::delete('settings/tokens/{apiToken}', [ApiTokenController::class, 'destroy'])->name('settings.tokens.destroy');
    Route::get('settings/password', [PasswordController::class, 'edit'])->name('settings.password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('settings.password.update');
    Route::get('settings/license', [\App\Http\Controllers\LicenseController::class, 'edit'])->name('settings.license.edit');
    Route::put('settings/license', [\App\Http\Controllers\LicenseController::class, 'update'])->name('settings.license.update');
    Route::post('settings/license/sync', [\App\Http\Controllers\LicenseController::class, 'sync'])->name('settings.license.sync');
    Route::get('settings/branding', [BrandingController::class, 'edit'])->name('settings.branding.edit');
    Route::put('settings/branding', [BrandingController::class, 'update'])->name('settings.branding.update');
    Route::get('settings/2fa', [TwoFactorController::class, 'show'])->name('settings.2fa.show');
    Route::post('settings/2fa/enable', [TwoFactorController::class, 'enable'])->name('settings.2fa.enable');
    Route::post('settings/2fa/confirm', [TwoFactorController::class, 'confirm'])->name('settings.2fa.confirm');
    Route::delete('settings/2fa', [TwoFactorController::class, 'disable'])->name('settings.2fa.disable');
    Route::get('settings/notifications', [NotificationController::class, 'edit'])->name('settings.notifications.edit');
    Route::put('settings/notifications', [NotificationController::class, 'update'])->name('settings.notifications.update');
    Route::post('settings/notifications/test', [NotificationController::class, 'test'])->name('settings.notifications.test');
    Route::get('settings/users', [UserController::class, 'index'])->name('settings.users.index');
    Route::get('settings/users/create', [UserController::class, 'create'])->name('settings.users.create');
    Route::post('settings/users', [UserController::class, 'store'])->name('settings.users.store');
    Route::get('settings/users/{user}/edit', [UserController::class, 'edit'])->name('settings.users.edit');
    Route::put('settings/users/{user}', [UserController::class, 'update'])->name('settings.users.update');
    Route::delete('settings/users/{user}', [UserController::class, 'destroy'])->name('settings.users.destroy');
    Route::get('settings/audit', [AuditLogController::class, 'index'])->name('settings.audit.index');
    Route::delete('settings/audit/selected', [AuditLogController::class, 'destroySelected'])->name('settings.audit.destroy-selected');
    Route::delete('settings/audit/all', [AuditLogController::class, 'destroyAll'])->name('settings.audit.destroy-all');

    // General settings (timezone, system info).
    Route::get('settings/general', [GeneralSettingsController::class, 'edit'])->name('settings.general.edit');
    Route::put('settings/general', [GeneralSettingsController::class, 'update'])->name('settings.general.update');

    // Firewall (admin-gated in the controller): sessions, IP bans, access limit.
    Route::get('settings/firewall', [FirewallController::class, 'index'])->name('settings.firewall.index');
    Route::put('settings/firewall', [FirewallController::class, 'update'])->name('settings.firewall.update');
    Route::post('settings/firewall/bans', [FirewallController::class, 'ban'])->name('settings.firewall.ban');
    Route::delete('settings/firewall/bans/{bannedIp}', [FirewallController::class, 'unban'])->name('settings.firewall.unban');
    Route::delete('settings/firewall/sessions/{id}', [FirewallController::class, 'revokeSession'])->name('settings.firewall.session.revoke');

    // Host & SSL (admin-gated in the controller): hostname + certificate management.
    Route::get('settings/host', [HostSslController::class, 'edit'])->name('settings.host.edit');
    Route::put('settings/host', [HostSslController::class, 'update'])->name('settings.host.update');
    Route::post('settings/host/letsencrypt', [HostSslController::class, 'letsencrypt'])->name('settings.host.letsencrypt');
    Route::post('settings/host/upload', [HostSslController::class, 'upload'])->name('settings.host.upload');
    Route::post('settings/host/self-signed', [HostSslController::class, 'selfSigned'])->name('settings.host.self-signed');

    // Maintenance (pruning + kopia maintenance windows).
    Route::get('settings/maintenance', [\App\Http\Controllers\MaintenanceController::class, 'edit'])->name('settings.maintenance.edit');
    Route::put('settings/maintenance', [\App\Http\Controllers\MaintenanceController::class, 'update'])->name('settings.maintenance.update');

    // Storage overview across directors.
    Route::get('settings/storage', [StorageDeviceController::class, 'index'])->name('settings.storage.index');

    // File Sync (main → many hosts).
    Route::get('settings/sync', [SyncFolderController::class, 'index'])->name('settings.sync.index');
    Route::get('settings/sync/create', [SyncFolderController::class, 'create'])->name('settings.sync.create');
    Route::post('settings/sync', [SyncFolderController::class, 'store'])->name('settings.sync.store');
    Route::get('settings/sync/{syncFolder}/edit', [SyncFolderController::class, 'edit'])->name('settings.sync.edit');
    Route::put('settings/sync/{syncFolder}', [SyncFolderController::class, 'update'])->name('settings.sync.update');
    Route::delete('settings/sync/{syncFolder}', [SyncFolderController::class, 'destroy'])->name('settings.sync.destroy');
    Route::patch('settings/sync/{syncFolder}/toggle', [SyncFolderController::class, 'toggle'])->name('settings.sync.toggle');
    Route::post('settings/sync/{syncFolder}/run', [SyncFolderController::class, 'runNow'])->name('settings.sync.run');

    // Placeholders — wired next.
    Route::get('/snapshots', [SnapshotController::class, 'index'])->name('snapshots.index');
    Route::get('/snapshots/{run}/browse', [SnapshotController::class, 'browse'])->name('snapshots.browse');
    Route::get('/restores', [RestoreController::class, 'index'])->name('restores.index');
    Route::get('/runs/{run}/restore', [RestoreController::class, 'create'])->name('restores.create');
    Route::post('/restores', [RestoreController::class, 'store'])->name('restores.store');

    Route::view('/ui', 'styleguide')->name('styleguide');
});
