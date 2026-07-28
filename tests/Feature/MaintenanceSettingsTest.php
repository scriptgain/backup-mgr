<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // A fresh database is a fresh install, which the setup wizard owns until
        // it is marked done; every other route redirects there.
        Setting::put('setup_complete', '1');
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => bcrypt('secret-password'),
            'role' => 'admin',
        ]);
    }

    public function test_the_maintenance_page_renders_the_prune_schedule(): void
    {
        $this->actingAs($this->admin())
            ->get(route('settings.maintenance.edit'))
            ->assertOk()
            ->assertSee('Scheduled Pruning')
            ->assertSee('Prune Repositories On A Schedule')
            ->assertSee('name="auto_prune_cron"', false);
    }

    public function test_the_general_page_no_longer_offers_a_prune_toggle(): void
    {
        $this->actingAs($this->admin())
            ->get(route('settings.general.edit'))
            ->assertOk()
            // The toggle here wrote a key no agent read; pruning lives on one page now.
            ->assertDontSee('name="prune_after_backup"', false)
            ->assertSee('Pruning and retention are configured under');
    }

    public function test_a_bad_prune_cron_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->put(route('settings.maintenance.update'), $this->form(['auto_prune_cron' => 'every night']))
            ->assertSessionHasErrors('auto_prune_cron');

        $this->assertNull(Setting::get('auto_prune_cron'));
    }

    public function test_the_prune_schedule_is_saved(): void
    {
        $this->actingAs($this->admin())
            ->put(route('settings.maintenance.update'), $this->form([
                'auto_prune_enabled' => '1',
                'auto_prune_cron' => '15 3 * * 0',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('1', Setting::get('auto_prune_enabled'));
        $this->assertSame('15 3 * * 0', Setting::get('auto_prune_cron'));
    }

    public function test_the_job_form_offers_a_prune_schedule(): void
    {
        $this->actingAs($this->admin())
            ->get(route('jobs.create'))
            ->assertOk()
            ->assertSee('Prune Schedule')
            ->assertSee('name="prune_schedule_cron"', false);
    }

    /** A full settings form, so a test can vary one field without tripping the rest. */
    private function form(array $overrides = []): array
    {
        return array_merge([
            'maintenance_window_start' => '02:00',
            'maintenance_window_end' => '05:00',
            'maintenance_days' => ['mon', 'tue'],
            'auto_prune_cron' => '30 4 * * *',
        ], $overrides);
    }
}
