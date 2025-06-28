<?php

namespace App\Notifications;

use App\Models\IntegrationSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class TokenExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $integration;

    public function __construct(IntegrationSetting $integration)
    {
        $this->integration = $integration;
    }

    public function via($notifiable)
    {
        return ['mail', 'slack', 'database'];
    }

    public function toMail($notifiable)
    {
        $expiresAt = Carbon::parse($this->integration->configuration['access_token_expires_at']);
        $hoursUntilExpiry = $expiresAt->diffInHours(now());

        return (new MailMessage)
            ->warning()
            ->subject("Token Expiring Soon - {$this->integration->vendor}")
            ->greeting("Token Expiring Soon")
            ->line("The access token for your {$this->integration->vendor} integration will expire soon.")
            ->line("**Expires in:** {$hoursUntilExpiry} hours")
            ->line("**Expires at:** {$expiresAt->format('Y-m-d H:i:s')}")
            ->action('Refresh Token', url('/integrations/' . $this->integration->id . '/refresh-token'))
            ->line('Please refresh the token to avoid sync interruptions.');
    }

    public function toSlack($notifiable)
    {
        $expiresAt = Carbon::parse($this->integration->configuration['access_token_expires_at']);
        $hoursUntilExpiry = $expiresAt->diffInHours(now());

        return (new SlackMessage)
            ->warning()
            ->content("Token expiring soon for {$this->integration->vendor} integration")
            ->attachment(function ($attachment) use ($hoursUntilExpiry, $expiresAt) {
                $attachment->title('Token Details')
                    ->fields([
                        'Expires in' => "{$hoursUntilExpiry} hours",
                        'Expires at' => $expiresAt->format('Y-m-d H:i:s')
                    ]);
            });
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'token_expiring',
            'integration_id' => $this->integration->id,
            'vendor' => $this->integration->vendor,
            'expires_at' => $this->integration->configuration['access_token_expires_at'],
            'hours_until_expiry' => Carbon::parse($this->integration->configuration['access_token_expires_at'])->diffInHours(now())
        ];
    }
}
