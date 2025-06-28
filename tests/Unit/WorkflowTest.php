<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Models\WorkflowExecution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function workflow_can_be_created()
    {
        $user = User::factory()->create();

        $workflowData = [
            'name' => 'Invoice Processing Workflow',
            'description' => 'Automated invoice processing',
            'trigger_event' => 'invoice.created',
            'trigger_conditions' => ['amount' => ['>', 1000]],
            'is_active' => true,
            'created_by' => $user->id,
        ];

        $workflow = Workflow::create($workflowData);

        $this->assertInstanceOf(Workflow::class, $workflow);
        $this->assertEquals('Invoice Processing Workflow', $workflow->name);
        $this->assertTrue($workflow->is_active);
        $this->assertEquals(['amount' => ['>', 1000]], $workflow->trigger_conditions);
    }

    /** @test */
    public function workflow_has_steps_relationship()
    {
        $workflow = Workflow::factory()->create();
        $step = WorkflowStep::factory()->create([
            'workflow_id' => $workflow->id,
            'order' => 1
        ]);

        $this->assertTrue($workflow->steps->contains($step));
        $this->assertEquals(1, $workflow->steps->count());
    }

    /** @test */
    public function workflow_has_executions_relationship()
    {
        $workflow = Workflow::factory()->create();
        $execution = WorkflowExecution::factory()->create([
            'workflow_id' => $workflow->id
        ]);

        $this->assertTrue($workflow->executions->contains($execution));
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
    public function workflow_can_get_latest_execution()
    {
        $workflow = Workflow::factory()->create();

        $oldExecution = WorkflowExecution::factory()->create([
            'workflow_id' => $workflow->id,
            'created_at' => now()->subHours(2)
        ]);

        $latestExecution = WorkflowExecution::factory()->create([
            'workflow_id' => $workflow->id,
            'created_at' => now()
        ]);

        $retrieved = $workflow->getLatestExecution();

        $this->assertEquals($latestExecution->id, $retrieved->id);
    }

    /** @test */
    public function workflow_calculates_success_rate_correctly()
    {
        $workflow = Workflow::factory()->create();

        // Create 3 successful executions
        WorkflowExecution::factory(3)->create([
            'workflow_id' => $workflow->id,
            'status' => 'completed'
        ]);

        // Create 1 failed execution
        WorkflowExecution::factory()->create([
            'workflow_id' => $workflow->id,
            'status' => 'failed'
        ]);

        $successRate = $workflow->getSuccessRate();

        $this->assertEquals(75.0, $successRate);
    }

    /** @test */
    public function workflow_returns_hundred_percent_success_rate_with_no_executions()
    {
        $workflow = Workflow::factory()->create();

        $successRate = $workflow->getSuccessRate();

        $this->assertEquals(100.0, $successRate);
    }

    /** @test */
    public function workflow_can_be_activated_and_deactivated()
    {
        $workflow = Workflow::factory()->create(['is_active' => false]);

        $workflow->update(['is_active' => true]);
        $this->assertTrue($workflow->fresh()->is_active);

        $workflow->update(['is_active' => false]);
        $this->assertFalse($workflow->fresh()->is_active);
    }

    /** @test */
    public function workflow_trigger_conditions_are_cast_to_array()
    {
        $workflow = Workflow::factory()->create([
            'trigger_conditions' => ['status' => 'pending']
        ]);

        $this->assertIsArray($workflow->trigger_conditions);
        $this->assertEquals(['status' => 'pending'], $workflow->trigger_conditions);
    }
}
