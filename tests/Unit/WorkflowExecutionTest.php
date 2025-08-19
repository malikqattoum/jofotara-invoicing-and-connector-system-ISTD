<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\WorkflowExecution;
use App\Models\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class WorkflowExecutionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function workflow_execution_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('workflow_executions'));

        $expectedColumns = [
            'id', 'workflow_id', 'status', 'trigger_data', 'context',
            'started_at', 'completed_at', 'failed_at', 'error_message',
            'created_at', 'updated_at'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('workflow_executions', $column),
                "WorkflowExecution table is missing column: {$column}"
            );
        }
    }

    /** @test */
    public function workflow_execution_belongs_to_workflow()
    {
        $workflow = Workflow::factory()->create();
        $execution = WorkflowExecution::factory()->create(['workflow_id' => $workflow->id]);

        $this->assertInstanceOf(Workflow::class, $execution->workflow);
        $this->assertEquals($workflow->id, $execution->workflow->id);
    }

    /** @test */
    public function workflow_execution_has_expected_fillable_fields()
    {
        $expectedFillable = [
            'workflow_id', 'status', 'trigger_data', 'context',
            'started_at', 'completed_at', 'failed_at', 'error_message'
        ];

        $execution = new WorkflowExecution();
        $actualFillable = $execution->getFillable();

        foreach ($expectedFillable as $field) {
            $this->assertContains($field, $actualFillable);
        }
    }

    /** @test */
    public function workflow_execution_casts_data_correctly()
    {
        $execution = WorkflowExecution::factory()->create([
            'trigger_data' => ['event' => 'invoice_created'],
            'context' => ['invoice_id' => 123],
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        $this->assertIsArray($execution->trigger_data);
        $this->assertIsArray($execution->context);
        $this->assertInstanceOf(\DateTime::class, $execution->started_at);
        $this->assertInstanceOf(\DateTime::class, $execution->completed_at);
    }

    /** @test */
    public function workflow_execution_status_methods_work_correctly()
    {
        $completed = WorkflowExecution::factory()->create(['status' => 'completed']);
        $this->assertTrue($completed->isCompleted());
        $this->assertFalse($completed->isFailed());
        $this->assertFalse($completed->isRunning());

        $failed = WorkflowExecution::factory()->create(['status' => 'failed']);
        $this->assertFalse($failed->isCompleted());
        $this->assertTrue($failed->isFailed());
        $this->assertFalse($failed->isRunning());

        $running = WorkflowExecution::factory()->create(['status' => 'running']);
        $this->assertFalse($running->isCompleted());
        $this->assertFalse($running->isFailed());
        $this->assertTrue($running->isRunning());
    }

    /** @test */
    public function workflow_execution_calculates_duration_correctly()
    {
        $execution = WorkflowExecution::factory()->create([
            'started_at' => now()->subMinutes(2),
            'completed_at' => now()
        ]);

        $this->assertEquals(120, $execution->getDuration());
    }
}
