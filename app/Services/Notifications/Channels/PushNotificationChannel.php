<?php

namespace App\Services\Notifications\Channels;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;
use Exception;

class PushNotificationChannel implements NotificationChannelInterface
{
    private $messaging;

    public function __construct()
    {
        $serviceAccount = config('services.firebase.credentials');

        if ($serviceAccount && file_exists($serviceAccount)) {
            $factory = (new Factory)->withServiceAccount($serviceAccount);
            $this->messaging = $factory->createMessaging();
        }
    }

    public function send(array $message, string $recipient, array $config = []): array
    {
        try {
            if (!$this->messaging) {
                throw new Exception('Firebase not configured');
            }

            $notification = Notification::create(
                $message['subject'],
                $message['body']
            );

            $cloudMessage = CloudMessage::withTarget('token', $recipient)
                ->withNotification($notification)
                ->withData([
                    'event' => $message['event'] ?? 'notification',
                    'priority' => $message['priority'] ?? 'normal',
                    'timestamp' => now()->toISOString()
                ]);

            if ($message['priority'] === 'high' || $message['priority'] === 'critical') {
                $cloudMessage = $cloudMessage->withAndroidConfig([
                    'priority' => 'high'
                ])->withApnsConfig([
                    'headers' => [
                        'apns-priority' => '10'
                    ]
                ]);
            }

            $result = $this->messaging->send($cloudMessage);

            return [
                'success' => true,
                'message' => 'Push notification sent successfully',
                'delivery_time' => now(),
                'message_id' => $result
            ];

        } catch (Exception $e) {
            Log::error("Push notification failed: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'failed_at' => now()
            ];
        }
    }

    public function isConfigured(): bool
    {
        $serviceAccount = config('services.firebase.credentials');
        return $serviceAccount && file_exists($serviceAccount);
    }

    public function getRequiredConfig(): array
    {
        return [
            'priority' => 'Notification priority (optional)'
        ];
    }
}
