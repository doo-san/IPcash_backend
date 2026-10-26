<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Support\AppConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AppSettingCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_are_read_from_the_database_once(): void
    {
        AppSetting::create(['key' => 'maintenance_enabled', 'value' => '1']);
        AppSetting::create(['key' => 'transfer_min_amount_xof', 'value' => '50']);

        DB::enableQueryLog();
        $this->assertTrue(AppConfig::bool('maintenance_enabled'));
        $this->assertSame(50, AppConfig::int('transfer_min_amount_xof'));
        $this->assertTrue(AppConfig::featureEnabled('transfer'));

        $settingsQueries = array_filter(
            DB::getQueryLog(),
            fn (array $query) => str_contains($query['query'], 'app_settings'),
        );
        $this->assertCount(1, $settingsQueries);
    }

    public function test_an_admin_change_is_visible_immediately(): void
    {
        $setting = AppSetting::create(['key' => 'maintenance_enabled', 'value' => '0']);
        $this->assertFalse(AppConfig::bool('maintenance_enabled'));

        $setting->update(['value' => '1']);
        $this->assertTrue(AppConfig::bool('maintenance_enabled'));

        $setting->delete();
        $this->assertFalse(AppConfig::bool('maintenance_enabled'));
    }

    public function test_the_public_config_reflects_a_saved_setting(): void
    {
        $this->getJson('/api/config')->assertJsonPath('maintenance.enabled', false);

        AppSetting::updateOrCreate(['key' => 'maintenance_enabled'], ['value' => '1']);

        $this->getJson('/api/config')->assertJsonPath('maintenance.enabled', true);
    }
}
