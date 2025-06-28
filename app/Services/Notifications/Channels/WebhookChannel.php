<?php

namespace App\Services\Notifications\Channels;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Exception;

class WebhookChannel implements NotificationChannelInterface
{
    private $client;

    public function __construct()
    {
        $this->client = new Client(['timeout' => 30]);
    }

    public function send(array $message, string $recipient, array $config = []): array
    {
        try {
            $method = $config['method'] ?? 'POST';
            $headers = $config['headers'] ?? ['Content-Type' => 'application/json'];
            $authentication = $config['authentication'] ?? [];

            // Add authentication headers if configured
            if (isset($authentication['type'])) {
                $headers = array_merge($headers, $this->buildAuthHeaders($authentication));
            }

            $payload = [
                'notification' => $message,
                'timestamp' => now()->toISOString(),
                'source' => 'integration-system'
            ];

            $response = $this->client->request($method, $recipient, [
                'headers' => $headers,
                'json' => $payload
            ]);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                return [
                    'success' => true,
                    'message' => 'Webhook notification sent successfully',
                    'delivery_time' => now(),
                    'status_code' => $response->getStatusCode()
                ];
            }

            throw new Exception('Webhook returned status: ' . $response->getStatusCode());

        } catch (Exception $e) {
            Log::error("Webhook notification failed: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'failed_at' => now()
            ];
        }
    }

    public function isConfigured(): bool
    {
        return true; // Webhook URLs are provided per notification
    }

    public function getRequiredConfig(): array
    {
        return [
            'method' => 'HTTP method (optional, default: POST)',
            'headers' => 'Custom headers (optional)',
            'authentication' => 'Authentication config (optional)'
        ];
    }

    private function buildAuthHeaders(array $authentication): array
    {
        $headers = [];

        switch ($authentication['type']) {
            case 'bearer':
                $headers['Authorization'] = 'Bearer ' . $authentication['token'];
                break;
            case 'basic':
                $credentials = base64_encode($authentication['username'] . ':' . $authentication['password']);
                $headers['Authorization'] = 'Basic ' . $credentials;
                break;
            case 'api_key':
                $headerName = $authentication['header'] ?? 'X-API-Key';
                $headers[$headerName] = $authentication['key'];
                break;
        }

        return $headers;
    }
}
