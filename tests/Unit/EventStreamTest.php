<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\EventStream;
use App\Models\User;
use App\Models\StreamedEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class EventStreamTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function event_stream_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('event_streams'));

        $expectedColumns = [
            'id', 'name', 'description', 'is_active', 'created_by', 'created_at', 'updated_at'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('event_streams', $column),
                "EventStream table is missing column: {$column}"
            );
        }
    }

    /** @test */
    public function event_stream_belongs_to_creator()
    {
        $user = User::factory()->create();
        $stream = EventStream::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $stream->createdBy);
        $this->assertEquals($user->id, $stream->createdBy->id);
    }

    /** @test */
    public function event_stream_has_many_events()
    {
        $stream = EventStream::factory()->create();
        $events = StreamedEvent::factory()->count(3)->create(['stream_id' => $stream->id]);

        $this->assertCount(3, $stream->events);
        $this->assertInstanceOf(StreamedEvent::class, $stream->events->first());
    }

    /** @test */
    public function event_stream_has_expected_fillable_fields()
    {
        $expectedFillable = [
            'name', 'description', 'is_active', 'created_by'
        ];

        $stream = new EventStream();
        $actualFillable = $stream->getFillable();

        foreach ($expectedFillable as $field) {
            $this->assertContains($field, $actualFillable);
        }
    }

    /** @test */
    public function event_stream_can_get_event_count()
    {
        $stream = EventStream::factory()->create();
        StreamedEvent::factory()->count(5)->create(['stream_id' => $stream->id]);

        $this->assertEquals(5, $stream->getEventCount());
    }

    /** @test */
    public function event_stream_can_get_active_subscriptions_count()
    {
        $stream = EventStream::factory()->create();
        $stream->subscriptions()->createMany([
            ['is_active' => true],
            ['is_active' => true],
            ['is_active' => false]
        ]);

        $this->assertEquals(2, $stream->getActiveSubscriptionsCount());
    }
}
