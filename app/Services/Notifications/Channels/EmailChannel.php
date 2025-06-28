<?php

namespace App\Services\Notifications\Channels;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;

class EmailChannel implements NotificationChannelInterface
{
    public function send(array $message, string $recipient, array $config = []): array
    {
        try {
            $template = $config['template'] ?? 'notifications.email.default';
            $fromEmail = $config['from_email'] ?? config('mail.from.address');
            $fromName = $config['from_name'] ?? config('mail.from.name');

            Mail::send($template, [
                'subject' => $message['subject'],
                'body' => $message['body'],
                'priority' => $message['priority'],
                'data' => $message['data'] ?? []
            ], function ($mail) use ($recipient, $message, $fromEmail, $fromName) {
                $mail->to($recipient)
                     ->from($fromEmail, $fromName)
                     ->subject($message['subject']);

                if ($message['priority'] === 'high') {
                    $mail->priority(1);
                }
            });

            return [
                'success' => true,
                'message' => 'Email sent successfully',
                'delivery_time' => now()
            ];

        } catch (Exception $e) {
            Log::error("Email notification failed: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'failed_at' => now()
            ];
        }
    }

    public function isConfigured(): bool
    {
        return config('mail.default') !== null &&
               config('mail.from.address') !== null;
    }

    public function getRequiredConfig(): array
    {
        return [
            'template' => 'Email template name (optional)',
            'from_email' => 'Sender email address (optional)',
            'from_name' => 'Sender name (optional)'
        ];
    }
}
