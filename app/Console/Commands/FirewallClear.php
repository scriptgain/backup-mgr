<?php

namespace App\Console\Commands;

use App\Models\BannedIp;
use App\Models\Setting;
use Illuminate\Console\Command;

/**
 * Emergency lockout recovery. Removes every IP ban and disables the
 * access-limit allowlist so the admin can get back in over SSH.
 */
class FirewallClear extends Command
{
    protected $signature = 'firewall:clear';

    protected $description = 'Emergency: remove all IP bans and disable the access-limit allowlist.';

    public function handle(): int
    {
        $count = BannedIp::query()->count();
        BannedIp::query()->delete();
        Setting::put('access_limit_enabled', '0');

        $this->info("Cleared {$count} IP ban(s) and disabled the access-limit allowlist.");

        return self::SUCCESS;
    }
}
