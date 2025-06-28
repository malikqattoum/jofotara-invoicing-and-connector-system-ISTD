<?php

namespace App\Services\Notifications\Channels;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Exception;

class SlackChannel implements NotificationChannelInterface
{
    private $client;

    public function __construct()
    {
        $this->client = new Client(['timeout' => 30]);
    }

    public function send(array $message, string $recipient, array $config = []): array
    {
        try {
            $webhookUrl = $config['webhook_url'] ?? config('services.slack.webhook_url');
            $channel = $config['channel'] ?? '#general';
            $username = $config['username'] ?? 'Integration Bot';
            $emoji = $config['emoji'] ?? ':robot_face:';

            if (!$webhookUrl) {
                throw new Exception('Slack webhook URL not configured');
            }

            $color = $this->getPriorityColor($message['priority'] ?? 'normal');

            $payload = [
                'channel' => $channel,
                'username' => $username,
                'icon_emoji' => $emoji,
                'attachments' => [
                    [
                        'color' => $color,
                        'title' => $message['subject'],
                        'text' => $message['body'],
                        'fields' => $this->buildFields($message),
                        'footer' => 'Integration System',
                        'ts' => now()->timestamp
                    ]
                ]
            ];

            $response = $this->client->post($webhookUrl, [
                'json' => $payload
            ]);

            if ($response->getStatusCode() === 200) {
                return [
                    'success' => true,
                    'message' => 'Slack notification sent successfully',
                    'delivery_time' => now()
                ];
            }

            throw new Exception('Slack API returned status: ' . $response->getStatusCode());

        } catch (Exception $e) {
            Log::error("Slack notification failed: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'failed_at' => now()
            ];
        }
    }

    public function isConfigured(): bool
    {
        return !empty(config('services.slack.webhook_url'));
    }

    public function getRequiredConfig(): array
    {
        return [
            'webhook_url' => 'Slack webhook URL',
            'channel' => 'Slack channel (optional, default: #general)',
            'username' => 'Bot username (optional)',
            'emoji' => 'Bot emoji (optional)'
        ];
    }

    private function getPriorityColor(string $priority): string
    {
        return match($priority) {
            'critical', 'high' => 'danger',
            'medium' => 'warning',
            'low', 'normal' => 'good',
            default => '#36a64f'
        };
    }

    private function buildFields(array $message): array
    {
        $fields = [];

        if (isset($message['event'])) {
            $fields[] = [
                'title' => 'Event',
                'value' => $message['event'],
                'short' => true
            ];
        }

        if (isset($message['priority'])) {
            $fields[] = [
                'title' => 'Priority',
                'value' => ucfirst($message['priority']),
                'short' => true
            ];
        }

        if (isset($message['data']['integration']['vendor'])) {
            $fields[] = [
                'title' => 'Integration',
                'value' => $message['data']['integration']['vendor'],
                'short' => true
            ];
        }

        return $fields;
    }
}
