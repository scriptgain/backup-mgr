<?php

namespace Database\Seeders;

use App\Models\BackupJob;
use App\Models\Director;
use App\Models\Host;
use App\Models\Location;
use App\Models\Repository;
use App\Models\RetentionPolicy;
use App\Models\Restore;
use App\Models\Run;
use App\Models\ScheduleTemplate;
use App\Models\Setting;
use App\Models\StorageDevice;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Populates a read-only public demo instance with believable sample data:
 * multiple sites, directors, repositories, hosts, and ~30 days of backup runs
 * (successes, failures, running) plus restores. Idempotent: wipes the demo
 * domain tables and rebuilds them. Never run on a real install.
 *
 *   php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    private function snap(): string
    {
        return bin2hex(random_bytes(32)); // 64-hex, restic-style
    }

    private function bytes(int $gb): int
    {
        return $gb * 1024 * 1024 * 1024;
    }

    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach (['restores', 'runs', 'backup_jobs', 'hosts', 'repositories', 'storage_devices', 'retention_policies', 'schedule_templates', 'directors', 'locations'] as $t) {
            DB::table($t)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // --- Users -------------------------------------------------------
        $admin = User::updateOrCreate(
            ['email' => 'demo@scriptgain.com'],
            ['name' => 'Demo Admin', 'password' => Hash::make(Str::random(40)), 'role' => 'admin', 'email_verified_at' => now()]
        );
        User::updateOrCreate(['email' => 'operator@scriptgain.com'],
            ['name' => 'Jordan Kim', 'password' => Hash::make(Str::random(40)), 'role' => 'operator', 'email_verified_at' => now()]);
        User::updateOrCreate(['email' => 'viewer@scriptgain.com'],
            ['name' => 'Alex Rivera', 'password' => Hash::make(Str::random(40)), 'role' => 'viewer', 'email_verified_at' => now()]);
        $uid = $admin->id;

        Setting::updateOrCreate(['key' => 'setup_complete'], ['value' => '1']);

        // --- Retention policies -----------------------------------------
        $rpStd = RetentionPolicy::create(['name' => 'Standard 30-Day', 'keep_latest' => 3, 'keep_daily' => 30, 'keep_weekly' => 8, 'keep_monthly' => 12, 'keep_annual' => 0]);
        $rpGfs = RetentionPolicy::create(['name' => 'GFS Long-Term', 'keep_latest' => 5, 'keep_daily' => 14, 'keep_weekly' => 12, 'keep_monthly' => 24, 'keep_annual' => 7]);
        $policies = [$rpStd->id, $rpGfs->id];

        // --- Schedule templates -----------------------------------------
        foreach ([
            ['Every Hour', '0 * * * *', 'Runs at the top of every hour.'],
            ['Every 6 Hours', '0 */6 * * *', 'Four times a day, every six hours.'],
            ['Daily at 2 AM', '0 2 * * *', 'Once a day, overnight.'],
            ['Twice Daily', '0 2,14 * * *', 'Overnight and mid-afternoon.'],
            ['Weekly (Sunday 3 AM)', '0 3 * * 0', 'Once a week, early Sunday morning.'],
            ['Monthly (1st, 4 AM)', '0 4 1 * *', 'Once a month, on the first.'],
        ] as [$tname, $cron, $tdesc]) {
            ScheduleTemplate::create(['name' => $tname, 'slug' => Str::slug($tname), 'cron' => $cron, 'description' => $tdesc, 'is_system' => true]);
        }

        // --- Locations + directors --------------------------------------
        $sites = [
            ['US East',    'Ashburn, Virginia',   'us-east-1',      'director-use1',  true],
            ['US West',    'San Jose, California', 'us-west-1',      'director-usw1',  false],
            ['EU Central', 'Frankfurt, Germany',   'eu-central-1',   'director-euc1',  false],
            ['APAC',       'Singapore',            'ap-southeast-1', 'director-apse1', false],
        ];

        $directors = [];
        foreach ($sites as [$name, $addr, $region, $dname, $isLocal]) {
            $loc = Location::create([
                'name' => $name, 'slug' => Str::slug($name), 'address' => $addr,
                'region' => $region, 'notes' => null,
            ]);
            $directors[] = Director::create([
                'location_id' => $loc->id, 'user_id' => $uid, 'name' => $dname,
                'slug' => Str::slug($dname), 'region' => $region, 'is_local' => $isLocal,
                'status' => 'online', 'api_key' => 'dk_'.Str::random(32),
                'version' => '1.4.1', 'last_seen_at' => now()->subSeconds(random_int(5, 90)),
            ]);
        }

        // --- Storage devices + repositories per director ----------------
        $repos = [];
        $repoBlueprints = [
            ['wasabi-use1',  's3',   ['bucket' => 'bkp-use1',  'endpoint' => 's3.us-east-1.wasabisys.com']],
            ['b2-offsite',   'b2',   ['bucket' => 'bkp-b2-offsite', 'endpoint' => 's3.us-west-004.backblazeb2.com']],
            ['minio-euc1',   's3',   ['bucket' => 'bkp-euc1',  'endpoint' => 'minio.euc1.internal:9000']],
            ['sftp-apse1',   'sftp', ['host' => 'store.apse1.internal', 'path' => '/srv/backups']],
        ];
        foreach ($directors as $i => $d) {
            $totGb = [4096, 2048, 8192, 2048][$i];
            $usedGb = (int) ($totGb * [0.44, 0.61, 0.37, 0.72][$i]);
            StorageDevice::create([
                'director_id' => $d->id, 'name' => 'nvme-array-0'.($i + 1),
                'mount_path' => '/var/backups', 'total_bytes' => $this->bytes($totGb),
                'used_bytes' => $this->bytes($usedGb), 'reported_at' => now()->subMinutes(random_int(1, 12)),
            ]);
            [$rname, $backend, $cfg] = $repoBlueprints[$i];
            $repos[] = Repository::create([
                'director_id' => $d->id, 'name' => $rname, 'backend' => $backend,
                'config' => $cfg, 'access_key_id' => $backend === 'sftp' ? null : 'AK'.Str::upper(Str::random(18)),
                'compression' => 'zstd', 'status' => 'active',
            ]);
        }

        // --- Hosts ------------------------------------------------------
        $hostDefs = [
            ['web-prod-01',    'ubuntu 22.04',           'online',  ['/', '/var/www', '/etc']],
            ['db-primary',     'debian 12',              'online',  ['/', '/var/lib/mysql']],
            ['app-node-2',     'ubuntu 22.04',           'online',  ['/', '/opt/app']],
            ['mail-gw',        'rocky 9',                'online',  ['/', '/var/vmail']],
            ['file-server',    'ubuntu 20.04',           'online',  ['/', '/srv/files', '/home']],
            ['k8s-worker-3',   'almalinux 9',            'online',  ['/', '/var/lib/kubelet']],
            ['win-dc-01',      'windows server 2022',    'online',  ['C:\\', 'D:\\']],
            ['redis-cache',    'debian 12',              'online',  ['/', '/var/lib/redis']],
            ['analytics-01',   'ubuntu 22.04',           'offline', ['/', '/data']],
            ['legacy-crm',     'centos 7',               'offline', ['/', '/var/www/html']],
            ['edge-proxy',     'ubuntu 22.04',           'online',  ['/', '/etc/nginx']],
            ['backup-staging', 'debian 12',              'pending', ['/']],
        ];
        $hosts = [];
        foreach ($hostDefs as $j => [$hn, $os, $st, $disks]) {
            $d = $directors[$j % count($directors)];
            // Randomly show each host online or offline (with the odd freshly
            // enrolled pending host), so the fleet looks live on every reseed.
            $roll = random_int(1, 100);
            $st = $roll <= 62 ? 'online' : ($roll <= 95 ? 'offline' : 'pending');
            $online = $st === 'online';
            $hosts[] = Host::create([
                'director_id' => $d->id, 'user_id' => $uid, 'name' => $hn,
                'connection_type' => 'agent',
                'hostname' => $hn.'.'.$d->slug.'.internal',
                'port' => null, 'username' => null, 'auth_type' => null,
                'disks' => $disks,
                'os' => $os, 'arch' => str_contains($os, 'windows') ? 'amd64' : 'x86_64',
                'agent_version' => '1.4.1',
                'api_key' => 'hk_'.Str::random(32),
                'status' => $st,
                'last_seen_at' => $st === 'pending' ? null : ($online ? now()->subSeconds(random_int(5, 120)) : now()->subHours(random_int(6, 40))),
            ]);
        }

        // Pick up to two online hosts to show a live, in-progress backup.
        $runningHosts = collect($hosts)->where('status', 'online')->shuffle()->take(2)->pluck('name')->all();

        // --- Backup jobs + runs -----------------------------------------
        $jobTypes = [
            'db-primary'  => ['mysql',    ['/var/lib/mysql']],
            'redis-cache' => ['files',    ['/var/lib/redis']],
            'win-dc-01'   => ['files',    ['C:\\Users', 'D:\\Shares']],
        ];
        $totalRuns = 0;
        foreach ($hosts as $k => $host) {
            if ($host->status === 'pending') {
                continue; // enrolled, no jobs yet
            }
            [$type, $src] = $jobTypes[$host->name] ?? ['files', $host->disks];
            $job = BackupJob::create([
                'host_id' => $host->id,
                'repository_id' => $repos[$k % count($repos)]->id,
                'retention_policy_id' => $policies[$k % count($policies)],
                'name' => $host->name.' '.($type === 'mysql' ? 'database' : 'files').' daily',
                'type' => $type, 'connector' => 'agent',
                'source' => $src, 'schedule_cron' => '0 '.random_int(1, 4).' * * *',
                'enabled' => true, 'ad_hoc' => false,
                'prune_after_backup' => true, 'prune_schedule_cron' => null,
            ]);

            // ~30 days of nightly runs, newest first.
            $days = random_int(24, 30);
            $offline = $host->status === 'offline';
            for ($n = 0; $n < $days; $n++) {
                $start = now()->subDays($n)->setTime(random_int(1, 4), random_int(0, 59), random_int(0, 59));
                // failures cluster; offline hosts fail their most recent runs.
                $roll = random_int(1, 100);
                if ($offline && $n < 3) {
                    $status = 'failed';
                } elseif ($roll <= 8) {
                    $status = 'failed';
                } elseif ($roll <= 12) {
                    $status = 'warn';
                } else {
                    $status = 'success';
                }

                $bytesIn = $this->bytes(random_int(6, 240));
                $ratio = $status === 'success' ? random_int(8, 34) / 100 : random_int(2, 12) / 100;
                $data = [
                    'backup_job_id' => $job->id,
                    'status' => $status,
                    'started_at' => $start,
                    'finished_at' => $status === 'failed' ? $start->copy()->addMinutes(random_int(1, 4)) : $start->copy()->addMinutes(random_int(3, 38)),
                    'bytes_in' => $bytesIn,
                    'bytes_uploaded' => (int) ($bytesIn * $ratio),
                    'files' => random_int(1_200, 480_000),
                    'snapshot_id' => in_array($status, ['success', 'warn'], true) ? $this->snap() : null,
                    'log' => $status === 'failed'
                        ? "starting snapshot\nscanning source\nconnection to repository lost"
                        : "starting snapshot\nscanning source\nuploaded new data\nsnapshot saved",
                    'error' => $status === 'failed'
                        ? ['connection to repository timed out', 'agent heartbeat lost mid-transfer', 'source path busy: lock held'][random_int(0, 2)]
                        : null,
                    'created_at' => $start,
                    'updated_at' => $start,
                ];
                Run::create($data);
                $totalRuns++;
            }

            // one currently-running job on a couple of online hosts
            if (in_array($host->name, ['web-prod-01', 'file-server'], true)) {
                Run::create([
                    'backup_job_id' => $job->id, 'status' => 'running',
                    'started_at' => now()->subMinutes(random_int(2, 18)), 'finished_at' => null,
                    'bytes_in' => $this->bytes(random_int(20, 90)), 'bytes_uploaded' => $this->bytes(random_int(1, 12)),
                    'files' => random_int(5_000, 90_000), 'snapshot_id' => null,
                    'log' => "starting snapshot\nscanning source\nuploading...", 'error' => null,
                ]);
                $totalRuns++;
            }
        }

        // --- Restores ---------------------------------------------------
        $successRuns = Run::where('status', 'success')->inRandomOrder()->limit(6)->get();
        $restoreStatuses = ['success', 'success', 'success', 'success', 'running', 'failed'];
        foreach ($successRuns as $i => $r) {
            $job = BackupJob::find($r->backup_job_id);
            Restore::create([
                'run_id' => $r->id, 'host_id' => $job?->host_id,
                'snapshot_id' => $r->snapshot_id,
                'paths' => ['/etc', '/var/www'], 'target_path' => '/restore/'.Str::random(6),
                'status' => $restoreStatuses[$i] ?? 'success',
                'log' => "restoring snapshot {$r->snapshot_id}\nrestored 1 snapshot",
                'created_at' => now()->subDays(random_int(0, 14)),
                'updated_at' => now()->subDays(random_int(0, 14)),
            ]);
        }

        $this->command?->info("Demo data seeded: ".count($hosts)." hosts, {$totalRuns} runs, ".Restore::count()." restores.");
    }
}
