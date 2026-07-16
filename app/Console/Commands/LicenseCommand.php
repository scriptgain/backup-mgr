<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\LicenseClient;
use Illuminate\Console\Command;

class LicenseCommand extends Command
{
    protected $signature = 'backup:license {key? : Set/replace the license key} {--clear : Remove the stored key}';

    protected $description = 'Set, clear, or re-check this install\'s BackupMGR license (scriptgain.com).';

    public function handle(): int
    {
        if ($this->option('clear')) {
            Setting::put('license_key', null);
            $this->warn('License key cleared.');
            LicenseClient::refresh();

            return self::SUCCESS;
        }

        if ($key = $this->argument('key')) {
            Setting::put('license_key', trim($key));
            $this->info('License key saved. Validating...');
        }

        $status = LicenseClient::refresh();

        $this->line('');
        $this->line('  Product : ' . config('license.product'));
        $this->line('  Endpoint: ' . config('license.endpoint'));
        $this->line('  Device  : ' . LicenseClient::deviceId());
        $this->line('  State   : <fg=' . ($status['ok'] ? 'green' : 'red') . '>' . strtoupper($status['state']) . '</>');
        $this->line('  Message : ' . $status['message']);

        if (! empty($status['license']['expires_at'])) {
            $this->line('  Expires : ' . $status['license']['expires_at']);
        }
        if (isset($status['license']['seats'])) {
            $seats = $status['license']['seats'];
            $this->line('  Seats   : ' . ($seats['used'] ?? '?') . ' / ' . ($seats['max'] ?? '?'));
        }
        $this->line('');

        return $status['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
