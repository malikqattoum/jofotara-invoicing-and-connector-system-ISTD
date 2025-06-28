<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class CircuitBreakerService
{
    protected $failureThreshold;
    protected $recoveryTimeout;
    protected $monitoringPeriod;

    public function __construct(
        int $failureThreshold = 5,
        int $recoveryTimeout = 300, // 5 minutes
        int $monitoringPeriod = 600 // 10 minutes
    ) {
        $this->failureThreshold = $failureThreshold;
        $this->recoveryTimeout = $recoveryTimeout;
        $this->monitoringPeriod = $monitoringPeriod;
    }

    public function call(string $serviceKey, callable $callback, callable $fallback = null)
    {
        $state = $this->getCircuitState($serviceKey);

        switch ($state) {
            case 'open':
                if ($this->shouldAttemptReset($serviceKey)) {
                    $this->setCircuitState($serviceKey, 'half-open');
                    return $this->executeCall($serviceKey, $callback, $fallback);
                } else {
                    return $this->executeFallback($serviceKey, $fallback);
                }

            case 'half-open':
                return $this->executeCall($serviceKey, $callback, $fallback);

            case 'closed':
            default:
                return $this->executeCall($serviceKey, $callback, $fallback);
        }
    }

    protected function executeCall(string $serviceKey, callable $callback, callable $fallback = null)
    {
        try {
            $result = $callback();
            $this->recordSuccess($serviceKey);
            return $result;
        } catch (Exception $e) {
            $this->recordFailure($serviceKey);

            if ($this->shouldOpenCircuit($serviceKey)) {
                $this->openCircuit($serviceKey);
            }

            if ($fallback) {
                return $this->executeFallback($serviceKey, $fallback);
            }

            throw $e;
        }
    }

    protected function executeFallback(string $serviceKey, callable $fallback = null)
    {
        if ($fallback) {
            try {
                return $fallback();
            } catch (Exception $e) {
                Log::error("Circuit breaker fallback failed for {$serviceKey}", [
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        throw new Exception("Service {$serviceKey} is currently unavailable (circuit breaker open)");
    }

    protected function getCircuitState(string $serviceKey): string
    {
        return Cache::get("circuit_breaker_state_{$serviceKey}", 'closed');
    }

    protected function setCircuitState(string $serviceKey, string $state): void
    {
        Cache::put("circuit_breaker_state_{$serviceKey}", $state, now()->addSeconds($this->recoveryTimeout));

        Log::info("Circuit breaker state changed", [
            'service' => $serviceKey,
            'state' => $state
        ]);
    }

    protected function recordSuccess(string $serviceKey): void
    {
        $currentState = $this->getCircuitState($serviceKey);

        if ($currentState === 'half-open') {
            $this->closeCircuit($serviceKey);
        }

        // Reset failure count on success
        Cache::forget("circuit_breaker_failures_{$serviceKey}");
    }

    protected function recordFailure(string $serviceKey): void
    {
        $failureKey = "circuit_breaker_failures_{$serviceKey}";
        $failures = Cache::get($failureKey, []);

        $failures[] = now()->timestamp;

        // Keep only failures within the monitoring period
        $cutoff = now()->subSeconds($this->monitoringPeriod)->timestamp;
        $failures = array_filter($failures, fn($timestamp) => $timestamp > $cutoff);

        Cache::put($failureKey, $failures, now()->addSeconds($this->monitoringPeriod));
    }

    protected function shouldOpenCircuit(string $serviceKey): bool
    {
        $failures = Cache::get("circuit_breaker_failures_{$serviceKey}", []);
        return count($failures) >= $this->failureThreshold;
    }

    protected function shouldAttemptReset(string $serviceKey): bool
    {
        $openedAt = Cache::get("circuit_breaker_opened_at_{$serviceKey}");

        if (!$openedAt) {
            return true;
        }

        return now()->timestamp - $openedAt > $this->recoveryTimeout;
    }

    protected function openCircuit(string $serviceKey): void
    {
        $this->setCircuitState($serviceKey, 'open');
        Cache::put("circuit_breaker_opened_at_{$serviceKey}", now()->timestamp, now()->addHours(24));

        Log::warning("Circuit breaker opened for service", [
            'service' => $serviceKey,
            'failure_threshold' => $this->failureThreshold
        ]);
    }

    protected function closeCircuit(string $serviceKey): void
    {
        $this->setCircuitState($serviceKey, 'closed');
        Cache::forget("circuit_breaker_failures_{$serviceKey}");
        Cache::forget("circuit_breaker_opened_at_{$serviceKey}");

        Log::info("Circuit breaker closed for service", [
            'service' => $serviceKey
        ]);
    }

    public function getCircuitStatus(string $serviceKey): array
    {
        $state = $this->getCircuitState($serviceKey);
        $failures = Cache::get("circuit_breaker_failures_{$serviceKey}", []);
        $openedAt = Cache::get("circuit_breaker_opened_at_{$serviceKey}");

        return [
            'service' => $serviceKey,
            'state' => $state,
            'failure_count' => count($failures),
            'failure_threshold' => $this->failureThreshold,
            'opened_at' => $openedAt ? date('Y-m-d H:i:s', $openedAt) : null,
            'recovery_timeout' => $this->recoveryTimeout,
            'monitoring_period' => $this->monitoringPeriod
        ];
    }

    public function resetCircuit(string $serviceKey): void
    {
        $this->closeCircuit($serviceKey);

        Log::info("Circuit breaker manually reset", [
            'service' => $serviceKey
        ]);
    }

    public function getAllCircuitStatuses(): array
    {
        $services = [];
        $cacheKeys = Cache::getRedis()->keys('*circuit_breaker_state_*');

        foreach ($cacheKeys as $key) {
            $serviceKey = str_replace('circuit_breaker_state_', '', $key);
            $services[] = $this->getCircuitStatus($serviceKey);
        }

        return $services;
    }
}
