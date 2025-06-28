<?php

namespace App\Services\EventStreaming;

use App\Models\EventStream;
use App\Models\EventSubscription;
use App\Models\StreamedEvent;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use SplObjectStorage;
use Exception;

class EventStreamingService implements MessageComponentInterface
{
    protected $connections;
    protected $subscriptions = [];
    protected $eventBuffer = [];
    protected $maxBufferSize = 1000;
    protected $flushInterval = 5; // seconds

    public function __construct()
    {
        $this->connections = new SplObjectStorage;
    }

    /**
     * Publish event to stream
     */
    public function publishEvent(string $stream, array $eventData, array $metadata = []): void
    {
        try {
            $event = [
                'id' => uniqid('event_'),
                'stream' => $stream,
                'type' => $eventData['type'] ?? 'generic',
                'data' => $eventData,
                'metadata' => array_merge($metadata, [
                    'timestamp' => now()->toISOString(),
                    'source' => 'integration_system'
                ]),
                'version' => 1
            ];

            // Store event in database for persistence
            $this->storeEvent($event);

            // Publish to Redis streams
            $this->publishToRedisStream($stream, $event);

            // Send to WebSocket connections
            $this->broadcastToWebSocket($stream, $event);

            // Buffer for batch processing
            $this->bufferEvent($stream, $event);

            Log::debug("Event published to stream", [
                'stream' => $stream,
                'event_id' => $event['id'],
                'type' => $event['type']
            ]);

        } catch (Exception $e) {
            Log::error("Failed to publish event", [
                'stream' => $stream,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Subscribe to event stream
     */
    public function subscribe(string $stream, callable $callback, array $filters = []): string
    {
        $subscriptionId = uniqid('sub_');

        $subscription = [
            'id' => $subscriptionId,
            'stream' => $stream,
            'callback' => $callback,
            'filters' => $filters,
            'created_at' => now(),
            'active' => true
        ];

        $this->subscriptions[$subscriptionId] = $subscription;

        // Store subscription in database
        EventSubscription::create([
            'subscription_id' => $subscriptionId,
            'stream_name' => $stream,
            'filters' => $filters,
            'status' => 'active',
            'created_by' => auth()->id()
        ]);

        Log::info("New subscription created", [
            'subscription_id' => $subscriptionId,
            'stream' => $stream
        ]);

        return $subscriptionId;
    }

    /**
     * Unsubscribe from stream
     */
    public function unsubscribe(string $subscriptionId): bool
    {
        if (isset($this->subscriptions[$subscriptionId])) {
            $this->subscriptions[$subscriptionId]['active'] = false;
            unset($this->subscriptions[$subscriptionId]);

            // Update database
            EventSubscription::where('subscription_id', $subscriptionId)
                ->update(['status' => 'inactive']);

            Log::info("Subscription cancelled", ['subscription_id' => $subscriptionId]);
            return true;
        }

        return false;
    }

    /**
     * Get events from stream
     */
    public function getEvents(string $stream, array $options = []): array
    {
        $limit = $options['limit'] ?? 100;
        $since = $options['since'] ?? null;
        $filters = $options['filters'] ?? [];

        $query = StreamedEvent::where('stream_name', $stream)
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        $events = $query->get();

        // Apply filters
        if (!empty($filters)) {
            $events = $events->filter(function ($event) use ($filters) {
                return $this->matchesFilters($event->event_data, $filters);
            });
        }

        return $events->values()->toArray();
    }

    /**
     * Create event stream
     */
    public function createStream(array $config): EventStream
    {
        $stream = EventStream::create([
            'name' => $config['name'],
            'description' => $config['description'] ?? '',
            'retention_days' => $config['retention_days'] ?? 30,
            'max_events' => $config['max_events'] ?? 1000000,
            'schema' => $config['schema'] ?? [],
            'configuration' => $config['configuration'] ?? [],
            'is_active' => $config['is_active'] ?? true,
            'created_by' => auth()->id()
        ]);

        // Initialize Redis stream
        $this->initializeRedisStream($stream->name);

        Log::info("Event stream created", [
            'stream_name' => $stream->name,
            'stream_id' => $stream->id
        ]);

        return $stream;
    }

    /**
     * Process buffered events
     */
    public function processBufferedEvents(): void
    {
        foreach ($this->eventBuffer as $stream => $events) {
            if (count($events) >= $this->maxBufferSize) {
                $this->flushEventBuffer($stream);
            }
        }

        // Time-based flush
        $this->flushStaleBuffers();
    }

    /**
     * WebSocket connection opened
     */
    public function onOpen(ConnectionInterface $conn): void
    {
        $this->connections->attach($conn);

        Log::info("WebSocket connection opened", [
            'connection_id' => $conn->resourceId
        ]);
    }

    /**
     * WebSocket message received
     */
    public function onMessage(ConnectionInterface $from, $msg): void
    {
        try {
            $data = json_decode($msg, true);

            if (!$data) {
                $from->send(json_encode(['error' => 'Invalid JSON']));
                return;
            }

            $action = $data['action'] ?? '';

            switch ($action) {
                case 'subscribe':
                    $this->handleWebSocketSubscribe($from, $data);
                    break;
                case 'unsubscribe':
                    $this->handleWebSocketUnsubscribe($from, $data);
                    break;
                case 'publish':
                    $this->handleWebSocketPublish($from, $data);
                    break;
                default:
                    $from->send(json_encode(['error' => 'Unknown action']));
            }

        } catch (Exception $e) {
            Log::error("WebSocket message error", [
                'connection_id' => $from->resourceId,
                'error' => $e->getMessage()
            ]);

            $from->send(json_encode(['error' => 'Message processing failed']));
        }
    }

    /**
     * WebSocket connection closed
     */
    public function onClose(ConnectionInterface $conn): void
    {
        $this->connections->detach($conn);

        Log::info("WebSocket connection closed", [
            'connection_id' => $conn->resourceId
        ]);
    }

    /**
     * WebSocket error occurred
     */
    public function onError(ConnectionInterface $conn, Exception $e): void
    {
        Log::error("WebSocket error", [
            'connection_id' => $conn->resourceId,
            'error' => $e->getMessage()
        ]);

        $conn->close();
    }

    /**
     * Start event streaming server
     */
    public function startServer(int $port = 8080): void
    {
        $server = \Ratchet\Server\IoServer::factory(
            new \Ratchet\Http\HttpServer(
                new \Ratchet\WebSocket\WsServer($this)
            ),
            $port
        );

        Log::info("Event streaming server started", ['port' => $port]);
        $server->run();
    }

    /**
     * Replay events from stream
     */
    public function replayEvents(string $stream, string $fromEventId, callable $callback): void
    {
        $events = StreamedEvent::where('stream_name', $stream)
            ->where('event_id', '>=', $fromEventId)
            ->orderBy('created_at')
            ->chunk(100, function ($events) use ($callback) {
                foreach ($events as $event) {
                    $callback($event->event_data);
                }
            });
    }

    /**
     * Get stream analytics
     */
    public function getStreamAnalytics(string $stream, int $hours = 24): array
    {
        $since = now()->subHours($hours);

        $events = StreamedEvent::where('stream_name', $stream)
            ->where('created_at', '>=', $since)
            ->get();

        $eventsByType = $events->groupBy('event_type')->map->count();
        $eventsPerHour = $events->groupBy(function ($event) {
            return $event->created_at->format('Y-m-d H:00');
        })->map->count();

        $subscribers = EventSubscription::where('stream_name', $stream)
            ->where('status', 'active')
            ->count();

        return [
            'stream_name' => $stream,
            'total_events' => $events->count(),
            'events_by_type' => $eventsByType->toArray(),
            'events_per_hour' => $eventsPerHour->toArray(),
            'active_subscribers' => $subscribers,
            'avg_events_per_hour' => round($events->count() / $hours, 2),
            'peak_hour' => $eventsPerHour->keys()->first() ?? null
        ];
    }

    /**
     * Clean up old events
     */
    public function cleanupOldEvents(): void
    {
        $streams = EventStream::where('is_active', true)->get();

        foreach ($streams as $stream) {
            $cutoffDate = now()->subDays($stream->retention_days);

            $deletedCount = StreamedEvent::where('stream_name', $stream->name)
                ->where('created_at', '<', $cutoffDate)
                ->delete();

            if ($deletedCount > 0) {
                Log::info("Cleaned up old events", [
                    'stream' => $stream->name,
                    'deleted_count' => $deletedCount
                ]);
            }
        }
    }

    /**
     * Store event in database
     */
    private function storeEvent(array $event): void
    {
        StreamedEvent::create([
            'event_id' => $event['id'],
            'stream_name' => $event['stream'],
            'event_type' => $event['type'],
            'event_data' => $event['data'],
            'metadata' => $event['metadata'],
            'version' => $event['version']
        ]);
    }

    /**
     * Publish to Redis stream
     */
    private function publishToRedisStream(string $stream, array $event): void
    {
        $redisKey = "stream:{$stream}";

        Redis::xadd($redisKey, '*', [
            'event_id' => $event['id'],
            'type' => $event['type'],
            'data' => json_encode($event['data']),
            'metadata' => json_encode($event['metadata'])
        ]);

        // Trim stream to prevent memory issues
        Redis::xtrim($redisKey, 'MAXLEN', '~', 10000);
    }

    /**
     * Broadcast to WebSocket connections
     */
    private function broadcastToWebSocket(string $stream, array $event): void
    {
        $message = json_encode([
            'type' => 'event',
            'stream' => $stream,
            'event' => $event
        ]);

        foreach ($this->connections as $connection) {
            // Check if connection is subscribed to this stream
            if ($this->isConnectionSubscribed($connection, $stream)) {
                $connection->send($message);
            }
        }
    }

    /**
     * Buffer event for batch processing
     */
    private function bufferEvent(string $stream, array $event): void
    {
        if (!isset($this->eventBuffer[$stream])) {
            $this->eventBuffer[$stream] = [];
        }

        $this->eventBuffer[$stream][] = [
            'event' => $event,
            'timestamp' => microtime(true)
        ];
    }

    /**
     * Flush event buffer for stream
     */
    private function flushEventBuffer(string $stream): void
    {
        if (!isset($this->eventBuffer[$stream])) {
            return;
        }

        $events = $this->eventBuffer[$stream];
        unset($this->eventBuffer[$stream]);

        // Process events with subscribers
        foreach ($this->subscriptions as $subscription) {
            if ($subscription['stream'] === $stream && $subscription['active']) {
                try {
                    foreach ($events as $bufferedEvent) {
                        $event = $bufferedEvent['event'];

                        if ($this->matchesFilters($event['data'], $subscription['filters'])) {
                            call_user_func($subscription['callback'], $event);
                        }
                    }
                } catch (Exception $e) {
                    Log::error("Subscription callback failed", [
                        'subscription_id' => $subscription['id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        Log::debug("Event buffer flushed", [
            'stream' => $stream,
            'events_count' => count($events)
        ]);
    }

    /**
     * Flush stale buffers based on time
     */
    private function flushStaleBuffers(): void
    {
        $staleThreshold = microtime(true) - $this->flushInterval;

        foreach ($this->eventBuffer as $stream => $events) {
            $oldestEvent = min(array_column($events, 'timestamp'));

            if ($oldestEvent < $staleThreshold) {
                $this->flushEventBuffer($stream);
            }
        }
    }

    /**
     * Check if filters match event data
     */
    private function matchesFilters(array $eventData, array $filters): bool
    {
        if (empty($filters)) {
            return true;
        }

        foreach ($filters as $filter) {
            $field = $filter['field'] ?? '';
            $operator = $filter['operator'] ?? '=';
            $value = $filter['value'] ?? '';

            $eventValue = data_get($eventData, $field);

            $matches = match($operator) {
                '=' => $eventValue == $value,
                '!=' => $eventValue != $value,
                '>' => $eventValue > $value,
                '<' => $eventValue < $value,
                'contains' => str_contains($eventValue, $value),
                'in' => in_array($eventValue, (array) $value),
                'regex' => preg_match($value, $eventValue),
                default => false
            };

            if (!$matches) {
                return false;
            }
        }

        return true;
    }

    /**
     * Initialize Redis stream
     */
    private function initializeRedisStream(string $streamName): void
    {
        $redisKey = "stream:{$streamName}";

        // Create stream if it doesn't exist
        try {
            Redis::xadd($redisKey, '*', ['init' => 'true']);
            Redis::xdel($redisKey, Redis::xread([$redisKey => '0-0'], 1)[$redisKey][0][0]);
        } catch (Exception $e) {
            // Stream might already exist
        }
    }

    /**
     * Handle WebSocket subscribe message
     */
    private function handleWebSocketSubscribe(ConnectionInterface $conn, array $data): void
    {
        $stream = $data['stream'] ?? '';
        $filters = $data['filters'] ?? [];

        if (!$stream) {
            $conn->send(json_encode(['error' => 'Stream name required']));
            return;
        }

        // Store connection subscription info
        $subscriptionId = uniqid('ws_sub_');
        $conn->subscriptions = $conn->subscriptions ?? [];
        $conn->subscriptions[$subscriptionId] = [
            'stream' => $stream,
            'filters' => $filters
        ];

        $conn->send(json_encode([
            'type' => 'subscribed',
            'subscription_id' => $subscriptionId,
            'stream' => $stream
        ]));
    }

    /**
     * Handle WebSocket unsubscribe message
     */
    private function handleWebSocketUnsubscribe(ConnectionInterface $conn, array $data): void
    {
        $subscriptionId = $data['subscription_id'] ?? '';

        if (isset($conn->subscriptions[$subscriptionId])) {
            unset($conn->subscriptions[$subscriptionId]);

            $conn->send(json_encode([
                'type' => 'unsubscribed',
                'subscription_id' => $subscriptionId
            ]));
        }
    }

    /**
     * Handle WebSocket publish message
     */
    private function handleWebSocketPublish(ConnectionInterface $conn, array $data): void
    {
        $stream = $data['stream'] ?? '';
        $eventData = $data['event'] ?? [];

        if (!$stream || empty($eventData)) {
            $conn->send(json_encode(['error' => 'Stream and event data required']));
            return;
        }

        $this->publishEvent($stream, $eventData, [
            'source' => 'websocket',
            'connection_id' => $conn->resourceId
        ]);

        $conn->send(json_encode([
            'type' => 'published',
            'stream' => $stream
        ]));
    }

    /**
     * Check if connection is subscribed to stream
     */
    private function isConnectionSubscribed(ConnectionInterface $conn, string $stream): bool
    {
        if (!isset($conn->subscriptions)) {
            return false;
        }

        foreach ($conn->subscriptions as $subscription) {
            if ($subscription['stream'] === $stream) {
                return true;
            }
        }

        return false;
    }
}
