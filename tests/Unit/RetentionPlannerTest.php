<?php

namespace Tests\Unit;

use App\Models\RetentionPolicy;
use App\Models\Run;
use App\Services\RetentionPlanner;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class RetentionPlannerTest extends TestCase
{
    /** Build unsaved Run models taken one day apart, newest first. */
    private function dailyRuns(int $count, string $startingFrom = '2026-07-26 02:00:00'): Collection
    {
        $base = Carbon::parse($startingFrom);

        return collect(range(0, $count - 1))->map(fn ($i) => new Run([
            'snapshot_id' => 'snap-' . $i,
            'started_at' => $base->copy()->subDays($i),
        ]));
    }

    public function test_it_keeps_everything_when_no_policy_is_attached(): void
    {
        $plan = (new RetentionPlanner)->plan($this->dailyRuns(11), null);

        $this->assertCount(11, $plan['keep']);
        $this->assertSame([], $plan['expire']);
    }

    public function test_a_policy_of_all_zeros_keeps_everything(): void
    {
        // Failing closed matters: treating "no rules" as "expire all" would
        // delete every backup in the repository.
        $policy = new RetentionPolicy([
            'keep_latest' => 0, 'keep_hourly' => 0, 'keep_daily' => 0,
            'keep_weekly' => 0, 'keep_monthly' => 0, 'keep_annual' => 0,
        ]);

        $plan = (new RetentionPlanner)->plan($this->dailyRuns(5), $policy);

        $this->assertCount(5, $plan['keep']);
        $this->assertSame([], $plan['expire']);
    }

    public function test_keep_daily_retains_the_newest_snapshot_per_day(): void
    {
        $policy = new RetentionPolicy(['keep_daily' => 3]);

        $plan = (new RetentionPlanner)->plan($this->dailyRuns(11), $policy);

        // One snapshot per day, so the three most recent days keep three.
        $this->assertSame(['snap-0', 'snap-1', 'snap-2'], $plan['keep']);
        $this->assertCount(8, $plan['expire']);
    }

    public function test_two_snapshots_on_one_day_keep_only_the_newer(): void
    {
        $policy = new RetentionPolicy(['keep_daily' => 2]);
        $runs = collect([
            new Run(['snapshot_id' => 'today-late', 'started_at' => Carbon::parse('2026-07-26 18:00:00')]),
            new Run(['snapshot_id' => 'today-early', 'started_at' => Carbon::parse('2026-07-26 02:00:00')]),
            new Run(['snapshot_id' => 'yesterday', 'started_at' => Carbon::parse('2026-07-25 02:00:00')]),
        ]);

        $plan = (new RetentionPlanner)->plan($runs, $policy);

        $this->assertContains('today-late', $plan['keep']);
        $this->assertContains('yesterday', $plan['keep']);
        $this->assertSame(['today-early'], $plan['expire']);
    }

    public function test_rules_union_so_a_weekly_bucket_saves_an_older_snapshot(): void
    {
        // The live policy that exposed the problem: keep_daily 3 + keep_weekly 4
        // against 11 nightly snapshots.
        $policy = new RetentionPolicy(['keep_daily' => 3, 'keep_weekly' => 4]);

        $plan = (new RetentionPlanner)->plan($this->dailyRuns(11), $policy);

        // Daily keeps the newest 3. Weekly keeps the newest snapshot in each of
        // the last 4 ISO weeks; 11 daily snapshots span 3 weeks, and the newest
        // of the current week is already kept by the daily rule, so the union
        // adds the newest snapshot of each earlier week.
        $this->assertContains('snap-0', $plan['keep']);
        $this->assertContains('snap-1', $plan['keep']);
        $this->assertContains('snap-2', $plan['keep']);
        $this->assertGreaterThan(3, count($plan['keep']));
        $this->assertLessThan(11, count($plan['keep']));
        $this->assertSame(11, count($plan['keep']) + count($plan['expire']));
    }

    public function test_keep_latest_is_honored_independently_of_buckets(): void
    {
        $policy = new RetentionPolicy(['keep_latest' => 5]);

        $plan = (new RetentionPlanner)->plan($this->dailyRuns(11), $policy);

        $this->assertSame(['snap-0', 'snap-1', 'snap-2', 'snap-3', 'snap-4'], $plan['keep']);
        $this->assertCount(6, $plan['expire']);
    }

    public function test_already_expired_and_snapshotless_runs_are_ignored(): void
    {
        $policy = new RetentionPolicy(['keep_latest' => 1]);
        $runs = collect([
            new Run(['snapshot_id' => 'live', 'started_at' => Carbon::parse('2026-07-26 02:00:00')]),
            new Run(['snapshot_id' => 'gone', 'started_at' => Carbon::parse('2026-07-25 02:00:00'), 'snapshot_expired_at' => Carbon::parse('2026-07-25 09:00:00')]),
            new Run(['snapshot_id' => null, 'started_at' => Carbon::parse('2026-07-24 02:00:00')]),
        ]);

        $plan = (new RetentionPlanner)->plan($runs, $policy);

        $this->assertSame(['live'], $plan['keep']);
        $this->assertSame([], $plan['expire']);
    }
}
