<?php

namespace App\Services\Notifications\Channels;

use Twilio\Rest\Client as TwilioClient;
use Illuminate\Support\Facades\Log;
use Exception;

class SMSChannel implements NotificationChannelInterface
{
    private $twilioClient;

    public function __construct()
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');

        if ($sid && $token) {
            $this->twilioClient = new TwilioClient($sid, $token);
        }
    }

    public function send(array $message, string $recipient, array $config = []): array
    {
        try {
            if (!$this->twilioClient) {
                throw new Exception('Twilio not configured');
            }

            $from = $config['from'] ?? config('services.twilio.from');

            if (!$from) {
                throw new Exception('Twilio from number not configured');
            }

            // Truncate message for SMS (160 character limit)
            $smsText = $this->formatForSMS($message);

            $twilioMessage = $this->twilioClient->messages->create(
                $recipient,
                [
                    'from' => $from,
                    'body' => $smsText
                ]
            );

            return [
                'success' => true,
                'message' => 'SMS sent successfully',
                'delivery_time' => now(),
                'provider_id' => $twilioMessage->sid
            ];

        } catch (Exception $e) {
            Log::error("SMS notification failed: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'failed_at' => now()
            ];
        }
    }

    public function isConfigured(): bool
    {
        return config('services.twilio.sid') &&
               config('services.twilio.token') &&
               config('services.twilio.from');
    }

    public function getRequiredConfig(): array
    {
        return [
            'from' => 'Twilio phone number (optional, uses default)'
        ];
    }

    private function formatForSMS(array $message): string
    {
        $text = $message['subject'];

        if (strlen($text) > 160) {
            $text = substr($text, 0, 157) . '...';
        }

        return $text;
    }
}
