<?php

namespace App\Services;

use App\Models\IntegrationSetting;
use App\Models\SyncLog;
use App\Models\NotificationRule;
use App\Notifications\IntegrationFailedNotification;
use App\Notifications\TokenExpiringNotification;
use App\Notifications\SyncCompletedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class IntegrationNotificationService
{
    public function checkAndSendNotifications(): void
    {
        $this->checkFailedIntegrations();
        $this->checkExpiringTokens();
        $this->checkSyncCompletions();
        $this->checkRateLimits();
    }

    public function sendIntegrationFailedNotification(IntegrationSetting $integration, SyncLog $syncLog): void
    {
        $rules = $this->getNotificationRules($integration, 'sync_failed');

        foreach ($rules as $rule) {
            if ($this->shouldSendNotification($rule, $syncLog)) {
                $this->sendNotification($rule, new IntegrationFailedNotification($integration, $syncLog));
                $this->recordNotificationSent($rule, $syncLog);
            }
        }
    }

    public function sendTokenExpiringNotification(IntegrationSetting $integration): void
    {
        $rules = $this->getNotificationRules($integration, 'token_expiring');

        foreach ($rules as $rule) {
            if ($this->shouldSendTokenExpiringNotification($rule, $integration)) {
                $this->sendNotification($rule, new TokenExpiringNotification($integration));
                $this->recordTokenExpiringNotificationSent($rule, $integration);
            }
        }
    }

    public function sendSyncCompletedNotification(IntegrationSetting $integration, SyncLog $syncLog): void
    {
        $rules = $this->getNotificationRules($integration, 'sync_completed');

        foreach ($rules as $rule) {
            if ($this->shouldSendSyncCompletedNotification($rule, $syncLog)) {
                $this->sendNotification($rule, new SyncCompletedNotification($integration, $syncLog));
                $this->recordNotificationSent($rule, $syncLog);
            }
        }
    }

    protected function checkFailedIntegrations(): void
    {
        $failedSyncs = SyncLog::where('status', 'failed')
            ->where('created_at', '>=', now()->subHours(1))
            ->with('integrationSetting')
            ->get();

        foreach ($failedSyncs as $syncLog) {
            $this->sendIntegrationFailedNotification($syncLog->integrationSetting, $syncLog);
        }
    }

    protected function checkExpiringTokens(): void
    {
        $integrations = IntegrationSetting::where('is_active', true)
            ->whereNotNull('configuration->access_token_expires_at')
            ->get();

        foreach ($integrations as $integration) {
            $expiresAt = $integration->configuration['access_token_expires_at'] ?? null;

            if ($expiresAt && Carbon::parse($expiresAt)->diffInHours(now()) <= 24) {
                $this->sendTokenExpiringNotification($integration);
            }
        }
    }

    protected function checkSyncCompletions(): void
    {
        $completedSyncs = SyncLog::where('status', 'success')
            ->where('created_at', '>=', now()->subHours(1))
            ->where('records_processed', '>', 0)
            ->with('integrationSetting')
            ->get();

        foreach ($completedSyncs as $syncLog) {
            $this->sendSyncCompletedNotification($syncLog->integrationSetting, $syncLog);
        }
    }

    protected function checkRateLimits(): void
    {
        // Check for rate limit violations and send notifications
        // This would depend on how you track rate limits
    }

    protected function getNotificationRules(IntegrationSetting $integration, string $eventType): \Illuminate\Database\Eloquent\Collection
    {
        return NotificationRule::where('integration_setting_id', $integration->id)
            ->where('event_type', $eventType)
            ->where('is_active', true)
            ->get();
    }

    protected function shouldSendNotification(NotificationRule $rule, SyncLog $syncLog): bool
    {
        // Check if we've already sent a notification for this sync log
        if ($this->hasRecentNotification($rule, $syncLog)) {
            return false;
        }

        // Check rule conditions
        return $this->evaluateRuleConditions($rule, $syncLog);
    }

    protected function shouldSendTokenExpiringNotification(NotificationRule $rule, IntegrationSetting $integration): bool
    {
        // Check if we've sent a token expiring notification recently
        $cacheKey = "token_expiring_notification_{$integration->id}_{$rule->id}";

        if (cache()->has($cacheKey)) {
            return false;
        }

        // Cache for 12 hours to avoid spam
        cache()->put($cacheKey, true, now()->addHours(12));

        return true;
    }

    protected function shouldSendSyncCompletedNotification(NotificationRule $rule, SyncLog $syncLog): bool
    {
        // Only send if configured and meets threshold
        $threshold = $rule->conditions['min_records'] ?? 0;

        return $syncLog->records_processed >= $threshold;
    }

    protected function hasRecentNotification(NotificationRule $rule, SyncLog $syncLog): bool
    {
        $cacheKey = "notification_sent_{$rule->id}_{$syncLog->id}";
        return cache()->has($cacheKey);
    }

    protected function evaluateRuleConditions(NotificationRule $rule, SyncLog $syncLog): bool
    {
        $conditions = $rule->conditions ?? [];

        // Check sync type condition
        if (isset($conditions['sync_types']) && !in_array($syncLog->sync_type, $conditions['sync_types'])) {
            return false;
        }

        // Check error pattern condition
        if (isset($conditions['error_patterns'])) {
            $errorMessage = strtolower($syncLog->error_message ?? '');
            $hasMatchingPattern = false;

            foreach ($conditions['error_patterns'] as $pattern) {
                if (str_contains($errorMessage, strtolower($pattern))) {
                    $hasMatchingPattern = true;
                    break;
                }
            }

            if (!$hasMatchingPattern) {
                return false;
            }
        }

        return true;
    }

    protected function sendNotification(NotificationRule $rule, $notification): void
    {
        try {
            $recipients = $this->getNotificationRecipients($rule);

            foreach ($recipients as $recipient) {
                Notification::route($rule->channel, $recipient)->notify($notification);
            }

            Log::info("Notification sent", [
                'rule_id' => $rule->id,
                'notification_type' => get_class($notification),
                'channel' => $rule->channel,
                'recipients_count' => count($recipients)
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send notification", [
                'rule_id' => $rule->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function getNotificationRecipients(NotificationRule $rule): array
    {
        return $rule->recipients ?? [];
    }

    protected function recordNotificationSent(NotificationRule $rule, SyncLog $syncLog): void
    {
        $cacheKey = "notification_sent_{$rule->id}_{$syncLog->id}";
        cache()->put($cacheKey, true, now()->addHours(24));
    }

    protected function recordTokenExpiringNotificationSent(NotificationRule $rule, IntegrationSetting $integration): void
    {
        $cacheKey = "token_expiring_notification_{$integration->id}_{$rule->id}";
        cache()->put($cacheKey, true, now()->addHours(12));
    }

    public function createNotificationRule(IntegrationSetting $integration, array $ruleData): NotificationRule
    {
        return NotificationRule::create([
            'integration_setting_id' => $integration->id,
            'name' => $ruleData['name'],
            'event_type' => $ruleData['event_type'],
            'channel' => $ruleData['channel'],
            'recipients' => $ruleData['recipients'],
            'conditions' => $ruleData['conditions'] ?? [],
            'is_active' => $ruleData['is_active'] ?? true
        ]);
    }
}
