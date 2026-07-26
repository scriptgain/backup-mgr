<?php

namespace App\Services;

use App\Models\RetentionPolicy;
use Illuminate\Support\Collection;

/**
 * Decides which of a job's snapshots a retention policy keeps.
 *
 * kopia can only expire snapshots that share a source, and until the
 * override-source change every pulled backup recorded its own throwaway source,
 * so years of snapshots sat outside any policy's reach. The master, though, knows
 * exactly which run produced which snapshot: planning expiry here works on that
 * history instead, so a repository can be brought in line with its policy
 * immediately, including snapshots kopia itself has no way to group.
 *
 * Bucket semantics match kopia's: a snapshot survives if ANY rule retains it, and
 * a rule retains the newest snapshot in each of its most recent N buckets.
 */
class RetentionPlanner
{
    /** Rule name => the date format whose distinct values define one bucket. */
    private const BUCKETS = [
        'keep_hourly' => 'Y-m-d H',
        'keep_daily' => 'Y-m-d',
        'keep_weekly' => 'o-W',
        'keep_monthly' => 'Y-m',
        'keep_annual' => 'Y',
    ];

    /**
     * @param  Collection  $runs  Runs holding a live snapshot, any order.
     * @return array{keep: list<string>, expire: list<string>, reasons: array<string, string>}
     */
    public function plan(Collection $runs, ?RetentionPolicy $policy): array
    {
        $ordered = $runs
            ->filter(fn ($r) => $r->snapshot_id && ! $r->snapshot_expired_at)
            ->sortByDesc(fn ($r) => $this->takenAt($r))
            ->values();

        $all = $ordered->pluck('snapshot_id')->all();

        // No policy, or a policy of all zeros, means keep everything. Expiring on
        // "no rules" would delete every backup, so this must fail closed.
        if (! $policy || ! $this->hasRules($policy)) {
            return ['keep' => $all, 'expire' => [], 'reasons' => []];
        }

        $keep = [];
        $reasons = [];

        $latest = (int) ($policy->keep_latest ?? 0);
        foreach ($ordered->take($latest) as $run) {
            $keep[$run->snapshot_id] = true;
            $reasons[$run->snapshot_id] = 'latest';
        }

        foreach (self::BUCKETS as $rule => $format) {
            $limit = (int) ($policy->{$rule} ?? 0);
            if ($limit <= 0) {
                continue;
            }
            $seen = [];
            foreach ($ordered as $run) {
                $bucket = $this->takenAt($run)->format($format);
                if (isset($seen[$bucket])) {
                    continue;   // an older snapshot in a bucket already covered
                }
                if (count($seen) >= $limit) {
                    break;      // past the N most recent buckets for this rule
                }
                $seen[$bucket] = true;
                $keep[$run->snapshot_id] = true;
                $reasons[$run->snapshot_id] ??= str_replace('keep_', '', $rule);
            }
        }

        return [
            'keep' => array_keys($keep),
            'expire' => array_values(array_diff($all, array_keys($keep))),
            'reasons' => $reasons,
        ];
    }

    /** Whether the policy retains anything at all. */
    private function hasRules(RetentionPolicy $policy): bool
    {
        foreach (array_merge(['keep_latest'], array_keys(self::BUCKETS)) as $rule) {
            if ((int) ($policy->{$rule} ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }

    /** When a run's snapshot was taken. Falls back through the run's timestamps. */
    private function takenAt($run): \Illuminate\Support\Carbon
    {
        $ts = $run->started_at ?: ($run->finished_at ?: $run->created_at);

        return \Illuminate\Support\Carbon::parse($ts ?: now());
    }
}
