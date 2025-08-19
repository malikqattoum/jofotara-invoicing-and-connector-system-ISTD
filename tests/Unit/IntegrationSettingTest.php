<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\IntegrationSetting;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class IntegrationSettingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function integration_setting_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('integration_settings'));

        $expectedColumns = [
            'id', 'user_id', 'type', 'is_active', 'credentials', 'configuration', 'last_sync', 'next_sync', 'sync_frequency', 'day_of_week', 'created_at', 'updated_at'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('integration_settings', $column),
                "IntegrationSetting table is missing column: {$column}"
            );
        }
    }

    /** @test */
    public function integration_setting_belongs_to_user()
    {
        $user = User::factory()->create();
        $setting = IntegrationSetting::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $setting->user);
        $this->assertEquals($user->id, $setting->user->id);
    }

    /** @test */
    public function integration_setting_has_expected_fillable_fields()
    {
        $expectedFillable = [
            'type', 'is_active', 'credentials', 'configuration', 'last_sync', 'next_sync', 'sync_frequency', 'day_of_week'
        ];

        $setting = new IntegrationSetting();
        $actualFillable = $setting->getFillable();

        foreach ($expectedFillable as $field) {
            $this->assertContains($field, $actualFillable);
        }
    }

    /** @test */
    public function integration_setting_can_get_frequency_description()
    {
        $dailySetting = IntegrationSetting::factory()->create(['sync_frequency' => 'daily']);
        $this->assertEquals('Daily', $dailySetting->frequencyDescription);

        $weeklySetting = IntegrationSetting::factory()->create(['sync_frequency' => 'weekly', 'day_of_week' => 2]);
        $this->assertEquals('Weekly (Tuesday)', $weeklySetting->frequencyDescription);
    }

    /** @test */
    public function integration_setting_can_get_day_name()
    {
        $setting = new IntegrationSetting();

        $this->assertEquals('Monday', $setting->getDayName(1));
        $this->assertEquals('Tuesday', $setting->getDayName(2));
        $this->assertEquals('Sunday', $setting->getDayName(7));
    }

    /** @test */
    public function integration_setting_can_scope_by_type()
    {
        IntegrationSetting::factory()->create(['type' => 'pos']);
        IntegrationSetting::factory()->create(['type' => 'bank']);

        $posSettings = IntegrationSetting::where('type', 'pos')->get();
        $this->assertCount(1, $posSettings);
        $this->assertEquals('pos', $posSettings->first()->type);
    }
}
