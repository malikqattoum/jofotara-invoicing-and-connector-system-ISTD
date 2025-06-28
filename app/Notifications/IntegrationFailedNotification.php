<?php

namespace App\Notifications;

use App\Models\IntegrationSetting;
use App\Models\SyncLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class IntegrationFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $integration;
    protected $syncLog;

    public function __construct(IntegrationSetting $integration, SyncLog $syncLog)
    {
        $this->integration = $integration;
        $this->syncLog = $syncLog;
    }

    public function via($notifiable)
    {
        return ['mail', 'slack', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->error()
            ->subject("Integration Sync Failed - {$this->integration->vendor}")
            ->greeting("Integration Sync Failed")
            ->line("The {$this->integration->vendor} integration sync has failed.")
            ->line("**Sync Type:** {$this->syncLog->sync_type}")
            ->line("**Error:** {$this->syncLog->error_message}")
            ->line("**Time:** {$this->syncLog->created_at->format('Y-m-d H:i:s')}")
            ->action('View Integration Dashboard', url('/integrations/' . $this->integration->id))
            ->line('Please check the integration settings and try again.');
    }

    public function toSlack($notifiable)
    {
        return (new SlackMessage)
            ->error()
            ->content("Integration sync failed for {$this->integration->vendor}")
            ->attachment(function ($attachment) {
                $attachment->title('Sync Details')
                    ->fields([
                        'Sync Type' => $this->syncLog->sync_type,
                        'Error' => $this->syncLog->error_message,
                        'Time' => $this->syncLog->created_at->format('Y-m-d H:i:s')
                    ]);
            });
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'integration_failed',
            'integration_id' => $this->integration->id,
            'vendor' => $this->integration->vendor,
            'sync_log_id' => $this->syncLog->id,
            'sync_type' => $this->syncLog->sync_type,
            'error_message' => $this->syncLog->error_message,
            'failed_at' => $this->syncLog->created_at
        ];
    }
}
