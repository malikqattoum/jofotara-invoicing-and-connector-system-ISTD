<?php

namespace App\Services\Notifications\Channels;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Exception;

class TeamsChannel implements NotificationChannelInterface
{
    private $client;

    public function __construct()
    {
        $this->client = new Client(['timeout' => 30]);
    }

    public function send(array $message, string $recipient, array $config = []): array
    {
        try {
            $webhookUrl = $config['webhook_url'] ?? config('services.teams.webhook_url');

            if (!$webhookUrl) {
                throw new Exception('Teams webhook URL not configured');
            }

            $color = $this->getPriorityColor($message['priority'] ?? 'normal');

            $payload = [
                '@type' => 'MessageCard',
                '@context' => 'https://schema.org/extensions',
                'summary' => $message['subject'],
                'themeColor' => $color,
                'sections' => [
                    [
                        'activityTitle' => $message['subject'],
                        'activitySubtitle' => $message['event'] ?? 'System Notification',
                        'text' => $message['body'],
                        'facts' => $this->buildFacts($message)
                    ]
                ]
            ];

            $response = $this->client->post($webhookUrl, [
                'json' => $payload
            ]);

            if ($response->getStatusCode() === 200) {
                return [
                    'success' => true,
                    'message' => 'Teams notification sent successfully',
                    'delivery_time' => now()
                ];
            }

            throw new Exception('Teams API returned status: ' . $response->getStatusCode());

        } catch (Exception $e) {
            Log::error("Teams notification failed: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'failed_at' => now()
            ];
        }
    }

    public function isConfigured(): bool
    {
        return !empty(config('services.teams.webhook_url'));
    }

    public function getRequiredConfig(): array
    {
        return [
            'webhook_url' => 'Microsoft Teams webhook URL'
        ];
    }

    private function getPriorityColor(string $priority): string
    {
        return match($priority) {
            'critical', 'high' => 'FF0000',
            'medium' => 'FFA500',
            'low', 'normal' => '00FF00',
            default => '0078D4'
        };
    }

    private function buildFacts(array $message): array
    {
        $facts = [];

        if (isset($message['priority'])) {
            $facts[] = [
                'name' => 'Priority',
                'value' => ucfirst($message['priority'])
            ];
        }

        if (isset($message['data']['integration']['vendor'])) {
            $facts[] = [
                'name' => 'Integration',
                'value' => $message['data']['integration']['vendor']
            ];
        }

        if (isset($message['metadata']['timestamp'])) {
            $facts[] = [
                'name' => 'Time',
                'value' => $message['metadata']['timestamp']
            ];
        }

        return $facts;
    }
}
