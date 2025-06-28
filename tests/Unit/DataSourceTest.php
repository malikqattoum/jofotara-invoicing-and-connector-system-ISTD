<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\DataSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DataSourceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function data_source_can_be_created()
    {
        $user = User::factory()->create();

        $sourceData = [
            'name' => 'Main Database',
            'type' => 'database',
            'connection_name' => 'main_db',
            'configuration' => [
                'host' => 'localhost',
                'port' => 5432,
                'database' => 'invoicing'
            ],
            'is_active' => true,
            'created_by' => $user->id,
        ];

        $source = DataSource::create($sourceData);

        $this->assertInstanceOf(DataSource::class, $source);
        $this->assertEquals('Main Database', $source->name);
        $this->assertEquals('database', $source->type);
        $this->assertTrue($source->is_active);
    }

    /** @test */
    public function data_source_belongs_to_creator()
    {
        $user = User::factory()->create();
        $source = DataSource::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $source->createdBy);
        $this->assertEquals($user->id, $source->createdBy->id);
    }

    /** @test */
    public function data_source_can_identify_database_type()
    {
        $source = DataSource::factory()->database()->create();

        $this->assertTrue($source->isDatabase());
        $this->assertFalse($source->isAPI());
        $this->assertFalse($source->isFile());
    }

    /** @test */
    public function data_source_can_identify_api_type()
    {
        $source = DataSource::factory()->api()->create();

        $this->assertTrue($source->isAPI());
        $this->assertFalse($source->isDatabase());
        $this->assertFalse($source->isFile());
    }

    /** @test */
    public function data_source_can_identify_file_type()
    {
        $source = DataSource::factory()->file()->create();

        $this->assertTrue($source->isFile());
        $this->assertFalse($source->isDatabase());
        $this->assertFalse($source->isAPI());
    }

    /** @test */
    public function data_source_can_test_connection()
    {
        $source = DataSource::factory()->create();

        $result = $source->testConnection();

        $this->assertTrue($result);
    }

    /** @test */
    public function data_source_configuration_is_cast_to_array()
    {
        $source = DataSource::factory()->create([
            'configuration' => ['host' => 'localhost', 'port' => 5432]
        ]);

        $this->assertIsArray($source->configuration);
        $this->assertEquals(['host' => 'localhost', 'port' => 5432], $source->configuration);
    }

    /** @test */
    public function data_source_can_be_activated_and_deactivated()
    {
        $source = DataSource::factory()->create(['is_active' => false]);

        $source->update(['is_active' => true]);
        $this->assertTrue($source->fresh()->is_active);

        $source->update(['is_active' => false]);
        $this->assertFalse($source->fresh()->is_active);
    }

    /** @test */
    public function data_source_has_correct_fillable_fields()
    {
        $expectedFillable = [
            'name',
            'type',
            'connection_name',
            'configuration',
            'is_active',
            'created_by'
        ];

        $source = new DataSource();
        $actualFillable = $source->getFillable();

        foreach ($expectedFillable as $field) {
            $this->assertContains($field, $actualFillable);
        }
    }

    /** @test */
    public function data_source_validates_different_configurations()
    {
        $databaseSource = DataSource::factory()->database()->create();
        $apiSource = DataSource::factory()->api()->create();
        $fileSource = DataSource::factory()->file()->create();

        // Database configuration
        $this->assertArrayHasKey('host', $databaseSource->configuration);
        $this->assertArrayHasKey('port', $databaseSource->configuration);
        $this->assertArrayHasKey('database', $databaseSource->configuration);

        // API configuration
        $this->assertArrayHasKey('base_url', $apiSource->configuration);
        $this->assertArrayHasKey('api_key', $apiSource->configuration);

        // File configuration
        $this->assertArrayHasKey('path', $fileSource->configuration);
        $this->assertArrayHasKey('format', $fileSource->configuration);
    }
}
