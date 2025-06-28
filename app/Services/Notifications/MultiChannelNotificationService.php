<?php

namespace App\Services\Notifications;

use App\Models\User;
use App\Models\NotificationRule;
use App\Models\NotificationLog;
use App\Models\IntegrationSetting;
use App\Services\Notifications\Channels\EmailChannel;
use App\Services\Notifications\Channels\SlackChannel;
use App\Services\Notifications\Channels\TeamsChannel;
use App\Services\Notifications\Channels\SMSChannel;
use App\Services\Notifications\Channels\WebhookChannel;
use App\Services\Notifications\Channels\PushNotificationChannel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Exception;

class MultiChannelNotificationService
{
    private $channels = [];
    private $enabledChannels = [];

    public function __construct()
    {
        $this->initializeChannels();
    }

    /**
     * Initialize notification channels
     */
    private function initializeChannels(): void
    {
        $this->channels = [
            'email' => new EmailChannel(),
            'slack' => new SlackChannel(),
            'teams' => new TeamsChannel(),
            'sms' => new SMSChannel(),
            'webhook' => new WebhookChannel(),
            'push' => new PushNotificationChannel()
        ];

        $this->enabledChannels = config('notifications.enabled_channels', ['email']);
    }

    /**
     * Send notification to multiple channels
     */
    public function send(string $event, array $data, array $recipients = [], array $channels = []): void
    {
        try {
            // Get applicable notification rules
            $rules = $this->getApplicableRules($event, $data);

            if (empty($rules)) {
                Log::debug("No notification rules found for event: {$event}");
                return;
            }

            foreach ($rules as $rule) {
                $this->processNotificationRule($rule, $event, $data, $recipients, $channels);
            }

        } catch (Exception $e) {
            Log::error("Failed to send notification for event {$event}: " . $e->getMessage());
        }
    }

    /**
     * Process individual notification rule
     */
    private function processNotificationRule(NotificationRule $rule, string $event, array $data, array $recipients, array $channels): void
    {
        try {
            // Check if rule conditions are met
            if (!$this->evaluateRuleConditions($rule, $data)) {
                return;
            }

            // Determine recipients
            $ruleRecipients = $this->determineRecipients($rule, $recipients);

            // Determine channels
            $ruleChannels = $this->determineChannels($rule, $channels);

            // Build notification message
            $message = $this->buildNotificationMessage($rule, $event, $data);

            // Send to each channel
            foreach ($ruleChannels as $channelName) {
                if (!isset($this->channels[$channelName])) {
                    Log::warning("Unknown notification channel: {$channelName}");
                    continue;
                }

                $this->sendToChannel($channelName, $message, $ruleRecipients, $rule);
            }

        } catch (Exception $e) {
            Log::error("Failed to process notification rule {$rule->id}: " . $e->getMessage());
        }
    }

    /**
     * Send notification to specific channel
     */
    private function sendToChannel(string $channelName, array $message, array $recipients, NotificationRule $rule): void
    {
        try {
            $channel = $this->channels[$channelName];

            foreach ($recipients as $recipient) {
                $channelRecipient = $this->formatRecipientForChannel($recipient, $channelName);

                if (!$channelRecipient) {
                    continue;
                }

                // Apply rate limiting
                if ($this->isRateLimited($rule, $channelName, $channelRecipient)) {
                    Log::info("Rate limited notification for {$channelName}: {$channelRecipient}");
                    continue;
                }

                // Send notification
                $result = $channel->send($message, $channelRecipient, $rule->configuration[$channelName] ?? []);

                // Log notification
                $this->logNotification($rule, $channelName, $channelRecipient, $message, $result);

                // Update rate limiting
                $this->updateRateLimit($rule, $channelName, $channelRecipient);
            }

        } catch (Exception $e) {
            Log::error("Failed to send notification via {$channelName}: " . $e->getMessage());
        }
    }

    /**
     * Get applicable notification rules for event
     */
    private function getApplicableRules(string $event, array $data): array
    {
        return NotificationRule::where('is_active', true)
            ->where(function ($query) use ($event) {
                $query->where('events', 'like', "%{$event}%")
                      ->orWhere('events', '*'); // Wildcard for all events
            })
            ->get()
            ->toArray();
    }

    /**
     * Evaluate rule conditions
     */
    private function evaluateRuleConditions(NotificationRule $rule, array $data): bool
    {
        $conditions = $rule->conditions ?? [];

        if (empty($conditions)) {
            return true; // No conditions means always trigger
        }

        foreach ($conditions as $condition) {
            if (!$this->evaluateCondition($condition, $data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate individual condition
     */
    private function evaluateCondition(array $condition, array $data): bool
    {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? '=';
        $value = $condition['value'] ?? '';

        $dataValue = data_get($data, $field);

        switch ($operator) {
            case '=':
                return $dataValue == $value;
            case '!=':
                return $dataValue != $value;
            case '>':
                return $dataValue > $value;
            case '<':
                return $dataValue < $value;
            case '>=':
                return $dataValue >= $value;
            case '<=':
                return $dataValue <= $value;
            case 'contains':
                return strpos($dataValue, $value) !== false;
            case 'in':
                return in_array($dataValue, (array) $value);
            default:
                return false;
        }
    }

    /**
     * Determine notification recipients
     */
    private function determineRecipients(NotificationRule $rule, array $providedRecipients): array
    {
        $recipients = [];

        // Add rule-defined recipients
        if (!empty($rule->recipients)) {
            $recipients = array_merge($recipients, $rule->recipients);
        }

        // Add provided recipients
        if (!empty($providedRecipients)) {
            $recipients = array_merge($recipients, $providedRecipients);
        }

        // Add role-based recipients
        if (!empty($rule->recipient_roles)) {
            $roleUsers = User::whereHas('roles', function ($query) use ($rule) {
                $query->whereIn('name', $rule->recipient_roles);
            })->get();

            foreach ($roleUsers as $user) {
                $recipients[] = [
                    'type' => 'user',
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'phone' => $user->phone
                ];
            }
        }

        return array_unique($recipients, SORT_REGULAR);
    }

    /**
     * Determine notification channels
     */
    private function determineChannels(NotificationRule $rule, array $providedChannels): array
    {
        $channels = [];

        // Add rule-defined channels
        if (!empty($rule->channels)) {
            $channels = array_merge($channels, $rule->channels);
        }

        // Add provided channels
        if (!empty($providedChannels)) {
            $channels = array_merge($channels, $providedChannels);
        }

        // Filter by enabled channels
        $channels = array_intersect($channels, $this->enabledChannels);

        return array_unique($channels);
    }

    /**
     * Build notification message
     */
    private function buildNotificationMessage(NotificationRule $rule, string $event, array $data): array
    {
        $template = $rule->message_template ?? $this->getDefaultTemplate($event);

        return [
            'subject' => $this->renderTemplate($template['subject'] ?? '', $data),
            'body' => $this->renderTemplate($template['body'] ?? '', $data),
            'priority' => $rule->priority ?? 'normal',
            'event' => $event,
            'data' => $data,
            'metadata' => [
                'rule_id' => $rule->id,
                'timestamp' => now()->toISOString()
            ]
        ];
    }

    /**
     * Render message template with data
     */
    private function renderTemplate(string $template, array $data): string
    {
        // Simple template rendering - replace {{field}} with actual values
        return preg_replace_callback('/\{\{([^}]+)\}\}/', function ($matches) use ($data) {
            $field = trim($matches[1]);
            return data_get($data, $field, '');
        }, $template);
    }

    /**
     * Format recipient for specific channel
     */
    private function formatRecipientForChannel(array $recipient, string $channelName): ?string
    {
        switch ($channelName) {
            case 'email':
                return $recipient['email'] ?? null;
            case 'sms':
                return $recipient['phone'] ?? null;
            case 'slack':
                return $recipient['slack_id'] ?? $recipient['email'] ?? null;
            case 'teams':
                return $recipient['teams_id'] ?? $recipient['email'] ?? null;
            case 'webhook':
                return $recipient['webhook_url'] ?? null;
            case 'push':
                return $recipient['device_token'] ?? null;
            default:
                return null;
        }
    }

    /**
     * Check if notification is rate limited
     */
    private function isRateLimited(NotificationRule $rule, string $channel, string $recipient): bool
    {
        $rateLimit = $rule->rate_limit ?? [];

        if (empty($rateLimit)) {
            return false;
        }

        $key = "notification_rate_limit:{$rule->id}:{$channel}:{$recipient}";
        $limit = $rateLimit['max_per_hour'] ?? 10;
        $window = $rateLimit['window_minutes'] ?? 60;

        $sent = cache()->get($key, 0);

        return $sent >= $limit;
    }

    /**
     * Update rate limit counter
     */
    private function updateRateLimit(NotificationRule $rule, string $channel, string $recipient): void
    {
        $rateLimit = $rule->rate_limit ?? [];

        if (empty($rateLimit)) {
            return;
        }

        $key = "notification_rate_limit:{$rule->id}:{$channel}:{$recipient}";
        $window = $rateLimit['window_minutes'] ?? 60;

        $current = cache()->get($key, 0);
        cache()->put($key, $current + 1, $window * 60);
    }

    /**
     * Log notification attempt
     */
    private function logNotification(NotificationRule $rule, string $channel, string $recipient, array $message, array $result): void
    {
        try {
            NotificationLog::create([
                'rule_id' => $rule->id,
                'channel' => $channel,
                'recipient' => $recipient,
                'message' => json_encode($message),
                'status' => $result['success'] ? 'sent' : 'failed',
                'response' => json_encode($result),
                'sent_at' => now()
            ]);
        } catch (Exception $e) {
            Log::error("Failed to log notification: " . $e->getMessage());
        }
    }

    /**
     * Get default message template for event
     */
    private function getDefaultTemplate(string $event): array
    {
        $templates = [
            'sync.completed' => [
                'subject' => 'Sync Completed - {{integration.vendor}}',
                'body' => 'Sync completed successfully for {{integration.vendor}}. {{stats.records_synced}} records processed.'
            ],
            'sync.failed' => [
                'subject' => 'Sync Failed - {{integration.vendor}}',
                'body' => 'Sync failed for {{integration.vendor}}. Error: {{error.message}}'
            ],
            'anomaly.detected' => [
                'subject' => 'Data Anomaly Detected - {{anomaly.type}}',
                'body' => 'An anomaly was detected: {{anomaly.description}}'
            ],
            'integration.error' => [
                'subject' => 'Integration Error - {{integration.vendor}}',
                'body' => 'Integration error occurred: {{error.message}}'
            ],
            'invoice.overdue' => [
                'subject' => 'Overdue Invoice Alert',
                'body' => 'Invoice {{invoice.number}} is overdue. Amount: {{invoice.total_amount}}'
            ]
        ];

        return $templates[$event] ?? [
            'subject' => 'Notification - {{event}}',
            'body' => 'Event {{event}} occurred.'
        ];
    }

    /**
     * Send bulk notifications
     */
    public function sendBulk(array $notifications): array
    {
        $results = [];

        foreach ($notifications as $notification) {
            try {
                $this->send(
                    $notification['event'],
                    $notification['data'],
                    $notification['recipients'] ?? [],
                    $notification['channels'] ?? []
                );

                $results[] = ['success' => true];
            } catch (Exception $e) {
                $results[] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Send notification with retry mechanism
     */
    public function sendWithRetry(string $event, array $data, array $recipients = [], array $channels = [], int $maxRetries = 3): void
    {
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                $this->send($event, $data, $recipients, $channels);
                return; // Success, exit retry loop
            } catch (Exception $e) {
                $attempt++;

                if ($attempt >= $maxRetries) {
                    Log::error("Failed to send notification after {$maxRetries} attempts: " . $e->getMessage());
                    throw $e;
                }

                // Wait before retry (exponential backoff)
                sleep(pow(2, $attempt));
            }
        }
    }

    /**
     * Queue notification for async processing
     */
    public function queue(string $event, array $data, array $recipients = [], array $channels = []): void
    {
        Queue::push(function () use ($event, $data, $recipients, $channels) {
            $this->send($event, $data, $recipients, $channels);
        });
    }

    /**
     * Test notification channels
     */
    public function testChannels(array $channels = [], string $recipient = null): array
    {
        $results = [];
        $testChannels = $channels ?: array_keys($this->channels);

        foreach ($testChannels as $channelName) {
            if (!isset($this->channels[$channelName])) {
                $results[$channelName] = [
                    'success' => false,
                    'error' => 'Channel not found'
                ];
                continue;
            }

            try {
                $testMessage = [
                    'subject' => 'Test Notification',
                    'body' => 'This is a test notification from the Multi-Channel Notification Service.',
                    'priority' => 'low'
                ];

                $testRecipient = $recipient ?: $this->getTestRecipient($channelName);

                if (!$testRecipient) {
                    $results[$channelName] = [
                        'success' => false,
                        'error' => 'No test recipient configured'
                    ];
                    continue;
                }

                $result = $this->channels[$channelName]->send($testMessage, $testRecipient, []);
                $results[$channelName] = $result;

            } catch (Exception $e) {
                $results[$channelName] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Get test recipient for channel
     */
    private function getTestRecipient(string $channelName): ?string
    {
        $testRecipients = config('notifications.test_recipients', []);
        return $testRecipients[$channelName] ?? null;
    }

    /**
     * Get notification statistics
     */
    public function getStatistics(array $filters = []): array
    {
        $query = NotificationLog::query();

        if (isset($filters['rule_id'])) {
            $query->where('rule_id', $filters['rule_id']);
        }

        if (isset($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }

        if (isset($filters['date_from'])) {
            $query->where('sent_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('sent_at', '<=', $filters['date_to']);
        }

        $stats = $query->selectRaw('
            channel,
            status,
            COUNT(*) as count,
            AVG(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as success_rate
        ')
        ->groupBy('channel', 'status')
        ->get();

        return $stats->groupBy('channel')->map(function ($channelStats) {
            $total = $channelStats->sum('count');
            $successful = $channelStats->where('status', 'sent')->sum('count');

            return [
                'total_sent' => $total,
                'successful' => $successful,
                'failed' => $total - $successful,
                'success_rate' => $total > 0 ? ($successful / $total * 100) : 0
            ];
        })->toArray();
    }

    /**
     * Create notification rule
     */
    public function createRule(array $ruleData): NotificationRule
    {
        return NotificationRule::create([
            'name' => $ruleData['name'],
            'description' => $ruleData['description'] ?? '',
            'events' => $ruleData['events'],
            'conditions' => $ruleData['conditions'] ?? [],
            'channels' => $ruleData['channels'],
            'recipients' => $ruleData['recipients'] ?? [],
            'recipient_roles' => $ruleData['recipient_roles'] ?? [],
            'message_template' => $ruleData['message_template'] ?? [],
            'priority' => $ruleData['priority'] ?? 'normal',
            'rate_limit' => $ruleData['rate_limit'] ?? [],
            'configuration' => $ruleData['configuration'] ?? [],
            'is_active' => $ruleData['is_active'] ?? true
        ]);
    }
}
