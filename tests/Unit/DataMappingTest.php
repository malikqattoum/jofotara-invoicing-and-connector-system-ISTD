<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\DataMapping;
use App\Models\IntegrationSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class DataMappingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function data_mapping_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('data_mappings'));

        $expectedColumns = [
            'id', 'integration_setting_id', 'source_field', 'target_field',
            'data_type', 'is_required', 'transformation_rule', 'created_at', 'updated_at'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('data_mappings', $column),
                "DataMapping table is missing column: {$column}"
            );
        }
    }

    /** @test */
    public function data_mapping_belongs_to_integration_setting()
    {
        $integration = IntegrationSetting::factory()->create();
        $mapping = DataMapping::factory()->create(['integration_setting_id' => $integration->id]);

        $this->assertInstanceOf(IntegrationSetting::class, $mapping->integrationSetting);
        $this->assertEquals($integration->id, $mapping->integrationSetting->id);
    }

    /** @test */
    public function data_mapping_has_expected_fillable_fields()
    {
        $expectedFillable = [
            'integration_setting_id', 'source_field', 'target_field',
            'data_type', 'is_required', 'transformation_rule'
        ];

        $mapping = new DataMapping();
        $actualFillable = $mapping->getFillable();

        foreach ($expectedFillable as $field) {
            $this->assertContains($field, $actualFillable);
        }
    }

    /** @test */
    public function data_mapping_scopes_work_correctly()
    {
        DataMapping::factory()->create(['data_type' => 'invoice']);
        DataMapping::factory()->create(['data_type' => 'customer']);
        DataMapping::factory()->create(['is_required' => true]);

        $this->assertCount(1, DataMapping::byDataType('invoice')->get());
        $this->assertCount(1, DataMapping::byDataType('customer')->get());
        $this->assertCount(1, DataMapping::required()->get());
    }

    /** @test */
    public function data_mapping_casts_fields_correctly()
    {
        $mapping = DataMapping::factory()->create([
            'is_required' => true,
            'transformation_rule' => ['operation' => 'uppercase']
        ]);

        $this->assertIsBool($mapping->is_required);
        $this->assertIsArray($mapping->transformation_rule);
    }
}
