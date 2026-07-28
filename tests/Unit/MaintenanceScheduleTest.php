<?php

namespace Tests\Unit;

use App\Http\Controllers\MaintenanceController;
use App\Rules\ValidCron;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MaintenanceScheduleTest extends TestCase
{
    private function settings(array $overrides = []): array
    {
        return array_merge(MaintenanceController::defaults(), $overrides);
    }

    public function test_a_valid_cron_passes_and_a_typo_fails(): void
    {
        $rules = ['cron' => ['nullable', new ValidCron]];

        $this->assertTrue(Validator::make(['cron' => '30 4 * * *'], $rules)->passes());
        $this->assertTrue(Validator::make(['cron' => '@daily'], $rules)->passes());
        // Blank means "no schedule", which `nullable` is there to allow.
        $this->assertTrue(Validator::make(['cron' => ''], $rules)->passes());

        $this->assertFalse(Validator::make(['cron' => 'every night'], $rules)->passes());
        $this->assertFalse(Validator::make(['cron' => '30 4 * *'], $rules)->passes());
        $this->assertFalse(Validator::make(['cron' => '99 4 * * *'], $rules)->passes());
    }

    public function test_there_is_no_next_prune_until_the_schedule_is_turned_on(): void
    {
        $this->assertNull(MaintenanceController::nextPruneAt($this->settings()));

        $this->assertNull(MaintenanceController::nextPruneAt(
            $this->settings(['auto_prune_enabled' => '1', 'auto_prune_cron' => 'nightly-ish'])
        ));
    }

    public function test_the_next_prune_follows_the_configured_cron(): void
    {
        Carbon::setTestNow('2026-07-28 01:00:00');

        $next = MaintenanceController::nextPruneAt(
            $this->settings(['auto_prune_enabled' => '1', 'auto_prune_cron' => '30 4 * * *'])
        );

        $this->assertNotNull($next);
        $this->assertSame('2026-07-28 04:30', $next->format('Y-m-d H:i'));

        Carbon::setTestNow();
    }

    public function test_maintenance_is_allowed_whenever_no_window_is_set(): void
    {
        $this->assertTrue(MaintenanceController::allowedNow($this->settings()));

        $this->assertFalse(MaintenanceController::allowedNow(
            $this->settings(['auto_maintenance' => '0'])
        ));
    }

    public function test_a_window_that_wraps_past_midnight_covers_both_sides(): void
    {
        $s = $this->settings([
            'maintenance_window_enabled' => '1',
            'maintenance_window_start' => '22:00',
            'maintenance_window_end' => '05:00',
        ]);

        $this->assertTrue(MaintenanceController::allowedNow($s, new \DateTime('2026-07-28 23:30:00')));
        $this->assertTrue(MaintenanceController::allowedNow($s, new \DateTime('2026-07-28 04:30:00')));
        $this->assertFalse(MaintenanceController::allowedNow($s, new \DateTime('2026-07-28 12:00:00')));
    }

    public function test_a_day_outside_the_selected_days_is_never_allowed(): void
    {
        $s = $this->settings([
            'maintenance_window_enabled' => '1',
            'maintenance_days' => 'mon,tue',
        ]);

        // 2026-07-28 is a Tuesday; the 29th is a Wednesday.
        $this->assertTrue(MaintenanceController::allowedNow($s, new \DateTime('2026-07-28 03:00:00')));
        $this->assertFalse(MaintenanceController::allowedNow($s, new \DateTime('2026-07-29 03:00:00')));
    }
}
