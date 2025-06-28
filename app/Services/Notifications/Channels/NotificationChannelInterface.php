<?php

namespace App\Services\Notifications\Channels;

interface NotificationChannelInterface
{
    /**
     * Send notification through this channel
     */
    public function send(array $message, string $recipient, array $config = []): array;

    /**
     * Check if channel is properly configured
     */
    public function isConfigured(): bool;

    /**
     * Get required configuration fields
     */
    public function getRequiredConfig(): array;
}
