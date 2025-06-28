<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\DataPipeline;
use App\Models\PipelineExecution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DataPipelineTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function data_pipeline_can_be_created()
    {
        $user = User::factory()->create();

        $pipelineData = [
            'name' => 'Invoice Data Pipeline',
            'description' => 'Processes invoice data from multiple sources',
            'data_sources' => ['api', 'csv'],
            'transformations' => ['normalize', 'validate'],
            'validation_rules' => ['amount' => 'required|numeric'],
            'destination' => ['type' => 'database', 'table' => 'invoices'],
            'schedule' => ['frequency' => 'daily', 'time' => '02:00'],
            'configuration' => ['batch_size' => 100],
            'is_active' => true,
            'created_by' => $user->id,
        ];

        $pipeline = DataPipeline::create($pipelineData);

        $this->assertInstanceOf(DataPipeline::class, $pipeline);
        $this->assertEquals('Invoice Data Pipeline', $pipeline->name);
        $this->assertTrue($pipeline->is_active);
        $this->assertEquals(['api', 'csv'], $pipeline->data_sources);
    }

    /** @test */
    public function data_pipeline_has_executions_relationship()
    {
        $pipeline = DataPipeline::factory()->create();
        $execution = PipelineExecution::factory()->create([
            'data_pipeline_id' => $pipeline->id
        ]);

        $this->assertTrue($pipeline->executions->contains($execution));
    }

    /** @test */
    public function data_pipeline_belongs_to_creator()
    {
        $user = User::factory()->create();
        $pipeline = DataPipeline::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $pipeline->createdBy);
        $this->assertEquals($user->id, $pipeline->createdBy->id);
    }

    /** @test */
    public function data_pipeline_can_get_latest_execution()
    {
        $pipeline = DataPipeline::factory()->create();

        $oldExecution = PipelineExecution::factory()->create([
            'data_pipeline_id' => $pipeline->id,
            'created_at' => now()->subHours(2)
        ]);

        $latestExecution = PipelineExecution::factory()->create([
            'data_pipeline_id' => $pipeline->id,
            'created_at' => now()
        ]);

        $retrieved = $pipeline->getLatestExecution();

        $this->assertEquals($latestExecution->id, $retrieved->id);
    }

    /** @test */
    public function data_pipeline_calculates_success_rate_correctly()
    {
        $pipeline = DataPipeline::factory()->create();

        // Create 4 successful executions
        PipelineExecution::factory(4)->create([
            'data_pipeline_id' => $pipeline->id,
            'status' => 'completed'
        ]);

        // Create 1 failed execution
        PipelineExecution::factory()->create([
            'data_pipeline_id' => $pipeline->id,
            'status' => 'failed'
        ]);

        $successRate = $pipeline->getSuccessRate();

        $this->assertEquals(80.0, $successRate);
    }

    /** @test */
    public function data_pipeline_returns_hundred_percent_success_rate_with_no_executions()
    {
        $pipeline = DataPipeline::factory()->create();

        $successRate = $pipeline->getSuccessRate();

        $this->assertEquals(100.0, $successRate);
    }

    /** @test */
    public function data_pipeline_casts_arrays_correctly()
    {
        $pipeline = DataPipeline::factory()->create([
            'data_sources' => ['source1', 'source2'],
            'transformations' => ['transform1', 'transform2'],
            'validation_rules' => ['rule1' => 'required'],
            'destination' => ['type' => 'database'],
            'schedule' => ['frequency' => 'hourly'],
            'configuration' => ['setting1' => 'value1']
        ]);

        $this->assertIsArray($pipeline->data_sources);
        $this->assertIsArray($pipeline->transformations);
        $this->assertIsArray($pipeline->validation_rules);
        $this->assertIsArray($pipeline->destination);
        $this->assertIsArray($pipeline->schedule);
        $this->assertIsArray($pipeline->configuration);
    }

    /** @test */
    public function data_pipeline_can_be_activated_and_deactivated()
    {
        $pipeline = DataPipeline::factory()->create(['is_active' => false]);

        $pipeline->update(['is_active' => true]);
        $this->assertTrue($pipeline->fresh()->is_active);

        $pipeline->update(['is_active' => false]);
        $this->assertFalse($pipeline->fresh()->is_active);
    }
}
