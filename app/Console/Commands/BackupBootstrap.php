<?php

namespace App\Console\Commands;

use App\Models\ApiToken;
use App\Models\Director;
use App\Models\ScheduleTemplate;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackupBootstrap extends Command
{
    protected $signature = 'backup:bootstrap {--fresh-token : Issue a new full-access token even if one exists}';

    protected $description = 'Ensure the local Director exists and issue a first full-access API token.';

    public function handle(): int
    {
        $director = Director::firstOrCreate(
            ['slug' => 'local'],
            ['name' => 'Local Director', 'is_local' => true, 'status' => 'online', 'region' => 'manager']
        );
        $this->info("Local director ready (#{$director->id}).");

        $templates = [
            ['Every Hour', '0 * * * *', 'Runs at the top of every hour.'],
            ['Every 6 Hours', '0 */6 * * *', 'Runs every six hours.'],
            ['Daily 2 AM', '0 2 * * *', 'Runs nightly at 2:00 AM.'],
            ['Daily Midnight', '0 0 * * *', 'Runs nightly at midnight.'],
            ['Weekly (Sun 3 AM)', '0 3 * * 0', 'Runs weekly on Sunday at 3:00 AM.'],
            ['Monthly (1st 4 AM)', '0 4 1 * *', 'Runs monthly on the 1st at 4:00 AM.'],
        ];
        foreach ($templates as [$name, $cron, $desc]) {
            ScheduleTemplate::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($name)],
                ['name' => $name, 'cron' => $cron, 'description' => $desc, 'is_system' => true]
            );
        }
        $this->info('Schedule templates seeded (' . count($templates) . ').');

        $user = User::orderBy('id')->first();
        if (! $user) {
            $this->error('No user exists yet; create the admin user first.');

            return self::FAILURE;
        }

        if ($this->option('fresh-token') || ApiToken::count() === 0) {
            [$token, $plain] = ApiToken::issue($user, 'bootstrap-full-access');
            Storage::disk('local')->put('bootstrap-token.txt', $plain . "\n");
            $this->info('Full-access API token written to storage/app/private/bootstrap-token.txt');
        } else {
            $this->line('API token already exists; use --fresh-token to issue another.');
        }

        return self::SUCCESS;
    }
}
