<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\AIInsight;
use App\Models\Organization;
use App\Models\Invoice;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class AIInsightTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ai_insight_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('ai_insights'));

        $expectedColumns = [
            'id', 'organization_id', 'insight_type', 'confidence_score', 'description',
            'action_items', 'is_actioned', 'created_at', 'updated_at'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('ai_insights', $column),
                "AIInsight table is missing column: {$column}"
            );
        }
    }

    #[Test]
    public function ai_insight_belongs_to_organization()
    {
        $organization = Organization::factory()->create();
        $insight = AIInsight::factory()->create(['organization_id' => $organization->id]);

        $this->assertInstanceOf(Organization::class, $insight->organization);
        $this->assertEquals($organization->id, $insight->organization->id);
    }

    #[Test]
    public function ai_insight_has_expected_fillable_fields()
    {
        $expectedFillable = [
            'organization_id', 'insight_type', 'confidence_score', 'description',
            'action_items', 'is_actioned'
        ];

        $insight = new AIInsight();
        $actualFillable = $insight->getFillable();

        foreach ($expectedFillable as $field) {
            $this->assertContains($field, $actualFillable);
        }
    }

    #[Test]
    public function ai_insight_can_be_marked_as_actioned()
    {
        $insight = AIInsight::factory()->create(['is_actioned' => false]);

        $insight->markAsActioned();

        $this->assertTrue($insight->fresh()->is_actioned);
    }

    #[Test]
    public function ai_insight_can_get_action_items_array()
    {
        $insight = AIInsight::factory()->create([
            'action_items' => json_encode(['item1', 'item2'])
        ]);

        $this->assertIsArray($insight->getActionItems());
        $this->assertCount(2, $insight->getActionItems());
    }

    #[Test]
    public function ai_insight_can_get_insight_description()
    {
        $insight = AIInsight::factory()->create([
            'insight_type' => 'payment_delay',
            'description' => 'Potential payment delay detected'
        ]);

        $this->assertStringContainsString('payment_delay', $insight->getDescription());
        $this->assertStringContainsString('Potential payment delay detected', $insight->getDescription());
    }
}
