<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\WorkflowStep;
use App\Models\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class WorkflowStepTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function workflow_step_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('workflow_steps'));

        $expectedColumns = [
            'id', 'workflow_id', 'name', 'type', 'configuration', 'conditions',
            'order', 'continue_on_failure', 'created_at', 'updated_at'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('workflow_steps', $column),
                "WorkflowStep table is missing column: {$column}"
            );
        }
    }

    /** @test */
    public function workflow_step_belongs_to_workflow()
    {
        $workflow = Workflow::factory()->create();
        $step = WorkflowStep::factory()->create(['workflow_id' => $workflow->id]);

        $this->assertInstanceOf(Workflow::class, $step->workflow);
        $this->assertEquals($workflow->id, $step->workflow->id);
    }

    /** @test */
    public function workflow_step_has_expected_fillable_fields()
    {
        $expectedFillable = [
            'workflow_id', 'name', 'type', 'configuration', 'conditions',
            'order', 'continue_on_failure'
        ];

        $step = new WorkflowStep();
        $actualFillable = $step->getFillable();

        foreach ($expectedFillable as $field) {
            $this->assertContains($field, $actualFillable);
        }
    }

    /** @test */
    public function workflow_step_casts_configuration_correctly()
    {
        $step = WorkflowStep::factory()->create([
            'configuration' => ['key' => 'value'],
            'conditions' => ['status' => 'completed'],
            'order' => 2,
            'continue_on_failure' => true
        ]);

        $this->assertIsArray($step->configuration);
        $this->assertIsArray($step->conditions);
        $this->assertIsInt($step->order);
        $this->assertIsBool($step->continue_on_failure);
    }

    /** @test */
    public function workflow_step_order_defaults_to_zero()
    {
        $step = WorkflowStep::factory()->create(['order' => null]);
        $this->assertEquals(0, $step->order);
    }
}
