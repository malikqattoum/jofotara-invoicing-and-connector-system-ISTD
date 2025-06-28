<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\IntegrationSetting;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IntegrationSettingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function integration_setting_can_be_created()
    {
        $organization = Organization::factory()->create();

        $settingData = [
            'organization_id' => $organization->id,
            'client_id' => 'test_client_id',
            'secret_key' => 'test_secret_key',
            'income_source_sequence' => 1,
            'environment_url' => 'https://test.example.com',
            'private_key_path' => '/path/to/private.key',
            'public_cert_path' => '/path/to/public.cert',
        ];

        $setting = IntegrationSetting::create($settingData);

        $this->assertInstanceOf(IntegrationSetting::class, $setting);
        $this->assertEquals('test_client_id', $setting->client_id);
        $this->assertEquals($organization->id, $setting->organization_id);
    }

    /** @test */
    public function integration_setting_belongs_to_organization()
    {
        $organization = Organization::factory()->create();
        $setting = IntegrationSetting::factory()->create([
            'organization_id' => $organization->id
        ]);

        $this->assertInstanceOf(Organization::class, $setting->organization);
        $this->assertEquals($organization->id, $setting->organization->id);
    }

    /** @test */
    public function integration_setting_has_required_fields()
    {
        $requiredFields = [
            'organization_id',
            'client_id',
            'secret_key',
            'income_source_sequence',
            'environment_url',
            'private_key_path',
            'public_cert_path'
        ];

        $fillableFields = (new IntegrationSetting())->getFillable();

        foreach ($requiredFields as $field) {
            $this->assertContains($field, $fillableFields);
        }
    }

    /** @test */
    public function integration_setting_can_be_updated()
    {
        $setting = IntegrationSetting::factory()->create([
            'client_id' => 'old_client_id'
        ]);

        $setting->update(['client_id' => 'new_client_id']);

        $this->assertEquals('new_client_id', $setting->fresh()->client_id);
    }

    /** @test */
    public function integration_setting_can_be_deleted()
    {
        $setting = IntegrationSetting::factory()->create();
        $settingId = $setting->id;

        $setting->delete();

        $this->assertNull(IntegrationSetting::find($settingId));
    }
}
