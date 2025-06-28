<?php

namespace App\Services\Security;

use App\Models\User;
use App\Models\AuditLog;
use App\Models\SecurityEvent;
use App\Models\IntegrationSetting;
use App\Models\DataEncryption;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;

class SecurityAuditService
{
    private $encryptionKey;
    private $auditableEvents = [
        'user.login',
        'user.logout',
        'user.login_failed',
        'integration.created',
        'integration.updated',
        'integration.deleted',
        'sync.started',
        'sync.completed',
        'data.accessed',
        'data.modified',
        'security.breach_detected',
        'permission.granted',
        'permission.revoked'
    ];

    public function __construct()
    {
        $this->encryptionKey = config('app.key');
    }

    /**
     * Log security event
     */
    public function logSecurityEvent(string $event, array $context = [], ?User $user = null): void
    {
        try {
            $user = $user ?: auth()->user();

            AuditLog::create([
                'user_id' => $user?->id,
                'event' => $event,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'context' => $this->encryptSensitiveData($context),
                'timestamp' => now(),
                'session_id' => session()->getId(),
                'severity' => $this->determineSeverity($event),
                'fingerprint' => $this->generateEventFingerprint($event, $context)
            ]);

            // Check for security threats
            $this->analyzeSecurityThreat($event, $context, $user);

        } catch (Exception $e) {
            Log::error("Failed to log security event: " . $e->getMessage());
        }
    }

    /**
     * Encrypt sensitive data
     */
    public function encryptSensitiveData(array $data): array
    {
        $encryptedData = [];
        $sensitiveFields = ['password', 'token', 'secret', 'api_key', 'private_key'];

        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields) || $this->containsSensitiveData($value)) {
                $encryptedData[$key] = $this->encryptValue($value);
            } else {
                $encryptedData[$key] = $value;
            }
        }

        return $encryptedData;
    }

    /**
     * Decrypt sensitive data
     */
    public function decryptSensitiveData(array $data): array
    {
        $decryptedData = [];

        foreach ($data as $key => $value) {
            if ($this->isEncryptedValue($value)) {
                $decryptedData[$key] = $this->decryptValue($value);
            } else {
                $decryptedData[$key] = $value;
            }
        }

        return $decryptedData;
    }

    /**
     * Field-level encryption for database
     */
    public function encryptField(string $value, string $fieldName): string
    {
        $encryptionRecord = DataEncryption::create([
            'field_name' => $fieldName,
            'encryption_algorithm' => 'AES-256-GCM',
            'created_at' => now(),
            'key_version' => 1
        ]);

        $encrypted = $this->encryptValue($value);

        return json_encode([
            'encrypted' => true,
            'value' => $encrypted,
            'encryption_id' => $encryptionRecord->id
        ]);
    }

    /**
     * Decrypt field value
     */
    public function decryptField(string $encryptedValue): string
    {
        $data = json_decode($encryptedValue, true);

        if (!$data || !isset($data['encrypted']) || !$data['encrypted']) {
            return $encryptedValue; // Not encrypted
        }

        return $this->decryptValue($data['value']);
    }

    /**
     * Role-based access control validation
     */
    public function validateAccess(User $user, string $resource, string $action): bool
    {
        try {
            // Check user permissions
            if ($user->can("{$action}_{$resource}")) {
                $this->logSecurityEvent('permission.granted', [
                    'resource' => $resource,
                    'action' => $action
                ], $user);
                return true;
            }

            // Log access denied
            $this->logSecurityEvent('permission.denied', [
                'resource' => $resource,
                'action' => $action,
                'reason' => 'insufficient_permissions'
            ], $user);

            return false;

        } catch (Exception $e) {
            Log::error("Access validation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Detect and analyze security threats
     */
    public function analyzeSecurityThreat(string $event, array $context, ?User $user): void
    {
        $threats = [];

        // Brute force detection
        if ($event === 'user.login_failed') {
            $threats = array_merge($threats, $this->detectBruteForce($user, $context));
        }

        // Suspicious activity detection
        if ($this->isSuspiciousActivity($event, $context, $user)) {
            $threats[] = [
                'type' => 'suspicious_activity',
                'severity' => 'medium',
                'description' => 'Unusual user activity detected'
            ];
        }

        // Data exfiltration detection
        if ($this->isDataExfiltration($event, $context)) {
            $threats[] = [
                'type' => 'data_exfiltration',
                'severity' => 'high',
                'description' => 'Potential data exfiltration detected'
            ];
        }

        // Privilege escalation detection
        if ($this->isPrivilegeEscalation($event, $context, $user)) {
            $threats[] = [
                'type' => 'privilege_escalation',
                'severity' => 'critical',
                'description' => 'Potential privilege escalation detected'
            ];
        }

        // Process detected threats
        foreach ($threats as $threat) {
            $this->processSecurityThreat($threat, $event, $context, $user);
        }
    }

    /**
     * Detect brute force attacks
     */
    private function detectBruteForce(?User $user, array $context): array
    {
        $threats = [];
        $ip = request()->ip();

        // Check failed login attempts by IP
        $ipFailures = AuditLog::where('ip_address', $ip)
            ->where('event', 'user.login_failed')
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();

        if ($ipFailures >= 5) {
            $threats[] = [
                'type' => 'brute_force_ip',
                'severity' => 'high',
                'description' => "Multiple failed login attempts from IP: {$ip}"
            ];
        }

        // Check failed login attempts by user
        if ($user) {
            $userFailures = AuditLog::where('user_id', $user->id)
                ->where('event', 'user.login_failed')
                ->where('created_at', '>=', now()->subMinutes(15))
                ->count();

            if ($userFailures >= 3) {
                $threats[] = [
                    'type' => 'brute_force_user',
                    'severity' => 'medium',
                    'description' => "Multiple failed login attempts for user: {$user->email}"
                ];
            }
        }

        return $threats;
    }

    /**
     * Detect suspicious activity
     */
    private function isSuspiciousActivity(string $event, array $context, ?User $user): bool
    {
        if (!$user) return false;

        // Check for unusual login times
        if ($event === 'user.login') {
            $hour = now()->hour;
            if ($hour < 6 || $hour > 22) { // Outside business hours
                return true;
            }
        }

        // Check for unusual location (simplified)
        if ($event === 'user.login' && isset($context['country'])) {
            $userCountry = $user->country ?? 'US';
            if ($context['country'] !== $userCountry) {
                return true;
            }
        }

        // Check for rapid data access
        if ($event === 'data.accessed') {
            $recentAccess = AuditLog::where('user_id', $user->id)
                ->where('event', 'data.accessed')
                ->where('created_at', '>=', now()->subMinutes(5))
                ->count();

            if ($recentAccess > 50) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect data exfiltration
     */
    private function isDataExfiltration(string $event, array $context): bool
    {
        // Check for large data exports
        if ($event === 'data.exported' && isset($context['record_count'])) {
            return $context['record_count'] > 10000;
        }

        // Check for unusual API usage
        if ($event === 'api.request' && isset($context['endpoint'])) {
            $endpoint = $context['endpoint'];
            $requests = Cache::get("api_requests_{$endpoint}", 0);

            if ($requests > 1000) { // Per hour limit
                return true;
            }
        }

        return false;
    }

    /**
     * Detect privilege escalation
     */
    private function isPrivilegeEscalation(string $event, array $context, ?User $user): bool
    {
        if (!$user) return false;

        // Check for permission changes
        if ($event === 'permission.granted' && isset($context['new_role'])) {
            $newRole = $context['new_role'];
            $adminRoles = ['admin', 'super_admin'];

            if (in_array($newRole, $adminRoles) && !$user->hasRole($adminRoles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Process security threat
     */
    private function processSecurityThreat(array $threat, string $event, array $context, ?User $user): void
    {
        // Log security event
        SecurityEvent::create([
            'type' => $threat['type'],
            'severity' => $threat['severity'],
            'description' => $threat['description'],
            'user_id' => $user?->id,
            'ip_address' => request()->ip(),
            'event' => $event,
            'context' => json_encode($context),
            'detected_at' => now(),
            'status' => 'active'
        ]);

        // Take automated actions based on severity
        switch ($threat['severity']) {
            case 'critical':
                $this->handleCriticalThreat($threat, $user);
                break;
            case 'high':
                $this->handleHighThreat($threat, $user);
                break;
            case 'medium':
                $this->handleMediumThreat($threat, $user);
                break;
        }
    }

    /**
     * Handle critical security threat
     */
    private function handleCriticalThreat(array $threat, ?User $user): void
    {
        // Immediately lock user account
        if ($user) {
            $user->update(['is_locked' => true, 'locked_at' => now()]);
        }

        // Block IP address
        $this->blockIpAddress(request()->ip(), 'critical_threat');

        // Send immediate alert
        $this->sendSecurityAlert($threat, 'critical');

        // Disable affected integrations
        $this->disableIntegrations($user);
    }

    /**
     * Handle high security threat
     */
    private function handleHighThreat(array $threat, ?User $user): void
    {
        // Temporary account lockout
        if ($user) {
            Cache::put("user_lockout_{$user->id}", true, 3600); // 1 hour
        }

        // Rate limit IP
        $this->rateLimitIpAddress(request()->ip());

        // Send security alert
        $this->sendSecurityAlert($threat, 'high');
    }

    /**
     * Handle medium security threat
     */
    private function handleMediumThreat(array $threat, ?User $user): void
    {
        // Require additional authentication
        if ($user) {
            Cache::put("require_2fa_{$user->id}", true, 1800); // 30 minutes
        }

        // Log for monitoring
        Log::warning("Medium security threat detected", [
            'threat' => $threat,
            'user_id' => $user?->id
        ]);
    }

    /**
     * Generate comprehensive security report
     */
    public function generateSecurityReport(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? now()->subDays(30);
        $dateTo = $filters['date_to'] ?? now();

        return [
            'summary' => $this->getSecuritySummary($dateFrom, $dateTo),
            'threats' => $this->getThreatAnalysis($dateFrom, $dateTo),
            'user_activity' => $this->getUserActivityAnalysis($dateFrom, $dateTo),
            'access_patterns' => $this->getAccessPatterns($dateFrom, $dateTo),
            'compliance' => $this->getComplianceMetrics($dateFrom, $dateTo),
            'recommendations' => $this->getSecurityRecommendations()
        ];
    }

    /**
     * Get security summary
     */
    private function getSecuritySummary(Carbon $dateFrom, Carbon $dateTo): array
    {
        $totalEvents = AuditLog::whereBetween('created_at', [$dateFrom, $dateTo])->count();
        $securityEvents = SecurityEvent::whereBetween('detected_at', [$dateFrom, $dateTo])->count();
        $failedLogins = AuditLog::where('event', 'user.login_failed')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->count();

        return [
            'total_events' => $totalEvents,
            'security_events' => $securityEvents,
            'failed_logins' => $failedLogins,
            'threat_level' => $this->calculateThreatLevel($securityEvents, $totalEvents),
            'active_users' => $this->getActiveUsersCount($dateFrom, $dateTo)
        ];
    }

    /**
     * Get threat analysis
     */
    private function getThreatAnalysis(Carbon $dateFrom, Carbon $dateTo): array
    {
        $threats = SecurityEvent::whereBetween('detected_at', [$dateFrom, $dateTo])
            ->selectRaw('type, severity, COUNT(*) as count')
            ->groupBy('type', 'severity')
            ->get();

        return [
            'by_type' => $threats->groupBy('type')->map->sum('count'),
            'by_severity' => $threats->groupBy('severity')->map->sum('count'),
            'timeline' => $this->getThreatTimeline($dateFrom, $dateTo)
        ];
    }

    /**
     * Compliance validation
     */
    public function validateCompliance(string $standard = 'GDPR'): array
    {
        $compliance = [];

        switch ($standard) {
            case 'GDPR':
                $compliance = $this->validateGDPRCompliance();
                break;
            case 'SOX':
                $compliance = $this->validateSOXCompliance();
                break;
            case 'HIPAA':
                $compliance = $this->validateHIPAACompliance();
                break;
        }

        return $compliance;
    }

    /**
     * Validate GDPR compliance
     */
    private function validateGDPRCompliance(): array
    {
        return [
            'data_encryption' => $this->checkDataEncryption(),
            'access_logging' => $this->checkAccessLogging(),
            'data_retention' => $this->checkDataRetention(),
            'consent_management' => $this->checkConsentManagement(),
            'data_portability' => $this->checkDataPortability(),
            'breach_notification' => $this->checkBreachNotification()
        ];
    }

    // Helper methods for encryption/decryption
    private function encryptValue($value): string
    {
        return Crypt::encrypt($value);
    }

    private function decryptValue(string $encrypted): string
    {
        return Crypt::decrypt($encrypted);
    }

    private function containsSensitiveData($value): bool
    {
        if (!is_string($value)) return false;

        $patterns = [
            '/\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b/', // Credit card
            '/\b\d{3}-\d{2}-\d{4}\b/', // SSN
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/' // Email
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    private function isEncryptedValue($value): bool
    {
        return is_string($value) && strpos($value, 'eyJ') === 0; // Base64 encrypted
    }

    private function determineSeverity(string $event): string
    {
        $severityMap = [
            'user.login_failed' => 'medium',
            'security.breach_detected' => 'critical',
            'permission.granted' => 'low',
            'data.accessed' => 'low',
            'data.modified' => 'medium'
        ];

        return $severityMap[$event] ?? 'low';
    }

    private function generateEventFingerprint(string $event, array $context): string
    {
        return hash('sha256', $event . serialize($context) . request()->ip());
    }

    // Additional helper methods (simplified implementations)
    private function blockIpAddress(string $ip, string $reason): void { /* Implementation */ }
    private function rateLimitIpAddress(string $ip): void { /* Implementation */ }
    private function sendSecurityAlert(array $threat, string $severity): void { /* Implementation */ }
    private function disableIntegrations(?User $user): void { /* Implementation */ }
    private function calculateThreatLevel(int $securityEvents, int $totalEvents): string { return 'low'; }
    private function getActiveUsersCount(Carbon $from, Carbon $to): int { return 0; }
    private function getThreatTimeline(Carbon $from, Carbon $to): array { return []; }
    private function getUserActivityAnalysis(Carbon $from, Carbon $to): array { return []; }
    private function getAccessPatterns(Carbon $from, Carbon $to): array { return []; }
    private function getComplianceMetrics(Carbon $from, Carbon $to): array { return []; }
    private function getSecurityRecommendations(): array { return []; }
    private function validateSOXCompliance(): array { return []; }
    private function validateHIPAACompliance(): array { return []; }
    private function checkDataEncryption(): bool { return true; }
    private function checkAccessLogging(): bool { return true; }
    private function checkDataRetention(): bool { return true; }
    private function checkConsentManagement(): bool { return true; }
    private function checkDataPortability(): bool { return true; }
    private function checkBreachNotification(): bool { return true; }
}
