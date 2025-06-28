<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\EventStream;
use App\Models\StreamedEvent;
use App\Models\EventSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EventStreamTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function event_stream_can_be_created()
    {
        $user = User::factory()->create();

        $streamData = [
            'name' => 'invoice-events',
            'description' => 'Stream for invoice-related events',
            'retention_days' => 30,
            'max_events' => 10000,
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'event_type' => ['type' => 'string'],
                    'invoice_id' => ['type' => 'integer']
                ]
            ],
            'configuration' => ['compression' => 'gzip'],
            'is_active' => true,
            'created_by' => $user->id,
        ];

        $stream = EventStream::create($streamData);

        $this->assertInstanceOf(EventStream::class, $stream);
        $this->assertEquals('invoice-events', $stream->name);
        $this->assertEquals(30, $stream->retention_days);
        $this->assertTrue($stream->is_active);
    }

    /** @test */
    public function event_stream_has_events_relationship()
    {
        $stream = EventStream::factory()->create(['name' => 'test-stream']);
        $event = StreamedEvent::factory()->create(['stream_name' => 'test-stream']);

        $this->assertTrue($stream->events->contains($event));
    }

    /** @test */
    public function event_stream_has_subscriptions_relationship()
    {
        $stream = EventStream::factory()->create(['name' => 'test-stream']);
        $subscription = EventSubscription::factory()->create(['stream_name' => 'test-stream']);

        $this->assertTrue($stream->subscriptions->contains($subscription));
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
    public function event_stream_can_count_events()
    {
        $stream = EventStream::factory()->create(['name' => 'test-stream']);

        StreamedEvent::factory(5)->create(['stream_name' => 'test-stream']);

        $this->assertEquals(5, $stream->getEventCount());
    }

    /** @test */
    public function event_stream_can_count_active_subscriptions()
    {
        $stream = EventStream::factory()->create(['name' => 'test-stream']);

        EventSubscription::factory(3)->create([
            'stream_name' => 'test-stream',
            'status' => 'active'
        ]);

        EventSubscription::factory(2)->create([
            'stream_name' => 'test-stream',
            'status' => 'inactive'
        ]);

        $this->assertEquals(3, $stream->getActiveSubscriptionsCount());
    }

    /** @test */
    public function event_stream_schema_is_cast_to_array()
    {
        $stream = EventStream::factory()->create([
            'schema' => ['type' => 'object', 'properties' => []]
        ]);

        $this->assertIsArray($stream->schema);
        $this->assertEquals(['type' => 'object', 'properties' => []], $stream->schema);
    }

    /** @test */
    public function event_stream_configuration_is_cast_to_array()
    {
        $stream = EventStream::factory()->create([
            'configuration' => ['compression' => 'gzip', 'batch_size' => 100]
        ]);

        $this->assertIsArray($stream->configuration);
        $this->assertEquals(['compression' => 'gzip', 'batch_size' => 100], $stream->configuration);
    }

    /** @test */
    public function event_stream_can_be_activated_and_deactivated()
    {
        $stream = EventStream::factory()->create(['is_active' => false]);

        $stream->update(['is_active' => true]);
        $this->assertTrue($stream->fresh()->is_active);

        $stream->update(['is_active' => false]);
        $this->assertFalse($stream->fresh()->is_active);
    }

    /** @test */
    public function event_stream_numeric_fields_are_cast_correctly()
    {
        $stream = EventStream::factory()->create([
            'retention_days' => 30,
            'max_events' => 10000
        ]);

        $this->assertIsInt($stream->retention_days);
        $this->assertIsInt($stream->max_events);
        $this->assertEquals(30, $stream->retention_days);
        $this->assertEquals(10000, $stream->max_events);
    }
}
