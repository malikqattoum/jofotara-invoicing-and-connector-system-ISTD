<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Models\User;
use App\Models\WorkflowExecution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function workflow_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('workflows'));

        $expectedColumns = [
            'id', 'name', 'description', 'trigger_event', 'trigger_conditions',
            'is_active', 'created_by', 'created_at', 'updated_at'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('workflows', $column),
                "Workflows table is missing column: {$column}"
            );
        }
    }

    /** @test */
    public function workflow_has_many_steps()
    {
        $workflow = Workflow::factory()->create();
        $steps = WorkflowStep::factory()->count(3)->create(['workflow_id' => $workflow->id]);

        $this->assertCount(3, $workflow->steps);
        $this->assertInstanceOf(WorkflowStep::class, $workflow->steps->first());
    }

    /** @test */
    public function workflow_has_many_executions()
    {
        $workflow = Workflow::factory()->create();
        $executions = WorkflowExecution::factory()->count(2)->create(['workflow_id' => $workflow->id]);

        $this->assertCount(2, $workflow->executions);
        $this->assertInstanceOf(WorkflowExecution::class, $workflow->executions->first());
    }

    /** @test */
    public function workflow_belongs_to_creator()
    {
        $user = User::factory()->create();
        $workflow = Workflow::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $workflow->createdBy);
        $this->assertEquals($user->id, $workflow->createdBy->id);
    }

    /** @test */
    public function workflow_has_expected_fillable_fields()
    {
        $expectedFillable = [
            'name', 'description', 'trigger_event', 'trigger_conditions',
            'is_active', 'created_by'
        ];

        $workflow = new Workflow();
        $actualFillable = $workflow->getFillable();

        foreach ($expectedFillable as $field) {
            $this->assertContains($field, $actualFillable);
        }
    }

    /** @test */
    public function workflow_can_get_latest_execution()
    {
        $workflow = Workflow::factory()->create();
        $oldExecution = WorkflowExecution::factory()->create([
            'workflow_id' => $workflow->id,
            'created_at' => now()->subDay()
        ]);
        $newExecution = WorkflowExecution::factory()->create([
            'workflow_id' => $workflow->id,
            'created_at' => now()
        ]);

        $this->assertEquals($newExecution->id, $workflow->getLatestExecution()->id);
    }

    /** @test */
    public function workflow_can_calculate_success_rate()
    {
        $workflow = Workflow::factory()->create();

        WorkflowExecution::factory()->count(3)->create([
            'workflow_id' => $workflow->id,
            'status' => 'completed'
        ]);

        WorkflowExecution::factory()->count(2)->create([
            'workflow_id' => $workflow->id,
            'status' => 'failed'
        ]);

        $this->assertEquals(60.0, $workflow->getSuccessRate());
    }
}
