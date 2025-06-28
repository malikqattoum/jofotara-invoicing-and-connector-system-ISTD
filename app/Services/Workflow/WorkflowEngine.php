<?php

namespace App\Services\Workflow;

use App\Models\Workflow;
use App\Models\WorkflowExecution;
use App\Models\WorkflowStep;
use App\Models\IntegrationSetting;
use App\Services\Notifications\MultiChannelNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Exception;

class WorkflowEngine
{
    private $notificationService;
    private $currentExecution;
    private $executionContext = [];

    public function __construct(MultiChannelNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Execute a workflow
     */
    public function executeWorkflow(Workflow $workflow, array $triggerData = []): WorkflowExecution
    {
        $execution = WorkflowExecution::create([
            'workflow_id' => $workflow->id,
            'status' => 'running',
            'trigger_data' => $triggerData,
            'started_at' => now(),
            'context' => []
        ]);

        $this->currentExecution = $execution;
        $this->executionContext = $triggerData;

        try {
            Log::info("Starting workflow execution", [
                'workflow_id' => $workflow->id,
                'execution_id' => $execution->id
            ]);

            $this->processWorkflowSteps($workflow);

            $execution->update([
                'status' => 'completed',
                'completed_at' => now(),
                'context' => $this->executionContext
            ]);

            Log::info("Workflow execution completed", [
                'workflow_id' => $workflow->id,
                'execution_id' => $execution->id
            ]);

        } catch (Exception $e) {
            $execution->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $e->getMessage(),
                'context' => $this->executionContext
            ]);

            Log::error("Workflow execution failed", [
                'workflow_id' => $workflow->id,
                'execution_id' => $execution->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }

        return $execution;
    }

    /**
     * Process workflow steps
     */
    private function processWorkflowSteps(Workflow $workflow): void
    {
        $steps = $workflow->steps()->orderBy('order')->get();

        foreach ($steps as $step) {
            if (!$this->shouldExecuteStep($step)) {
                continue;
            }

            $this->executeStep($step);
        }
    }

    /**
     * Execute individual workflow step
     */
    private function executeStep(WorkflowStep $step): void
    {
        Log::info("Executing workflow step", [
            'step_id' => $step->id,
            'type' => $step->type,
            'execution_id' => $this->currentExecution->id
        ]);

        $stepStartTime = microtime(true);

        try {
            $result = match($step->type) {
                'sync_integration' => $this->executeSyncStep($step),
                'send_notification' => $this->executeNotificationStep($step),
                'data_transformation' => $this->executeTransformationStep($step),
                'conditional_branch' => $this->executeConditionalStep($step),
                'http_request' => $this->executeHttpRequestStep($step),
                'delay' => $this->executeDelayStep($step),
                'approval_gate' => $this->executeApprovalStep($step),
                'custom_script' => $this->executeCustomScriptStep($step),
                default => throw new Exception("Unknown step type: {$step->type}")
            };

            $executionTime = (microtime(true) - $stepStartTime) * 1000;

            // Update execution context with step result
            $this->executionContext['steps'][$step->id] = [
                'status' => 'completed',
                'result' => $result,
                'execution_time' => $executionTime,
                'completed_at' => now()->toISOString()
            ];

        } catch (Exception $e) {
            $executionTime = (microtime(true) - $stepStartTime) * 1000;

            $this->executionContext['steps'][$step->id] = [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'execution_time' => $executionTime,
                'failed_at' => now()->toISOString()
            ];

            if ($step->continue_on_failure) {
                Log::warning("Step failed but continuing", [
                    'step_id' => $step->id,
                    'error' => $e->getMessage()
                ]);
                return;
            }

            throw $e;
        }
    }

    /**
     * Check if step should be executed based on conditions
     */
    private function shouldExecuteStep(WorkflowStep $step): bool
    {
        if (empty($step->conditions)) {
            return true;
        }

        foreach ($step->conditions as $condition) {
            if (!$this->evaluateCondition($condition)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate step condition
     */
    private function evaluateCondition(array $condition): bool
    {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? '=';
        $value = $condition['value'] ?? '';

        $contextValue = data_get($this->executionContext, $field);

        return match($operator) {
            '=' => $contextValue == $value,
            '!=' => $contextValue != $value,
            '>' => $contextValue > $value,
            '<' => $contextValue < $value,
            '>=' => $contextValue >= $value,
            '<=' => $contextValue <= $value,
            'contains' => str_contains($contextValue, $value),
            'in' => in_array($contextValue, (array) $value),
            'exists' => !is_null($contextValue),
            'not_exists' => is_null($contextValue),
            default => false
        };
    }

    /**
     * Execute sync integration step
     */
    private function executeSyncStep(WorkflowStep $step): array
    {
        $config = $step->configuration;
        $integrationId = $config['integration_id'] ?? null;

        if (!$integrationId) {
            throw new Exception('Integration ID not specified for sync step');
        }

        $integration = IntegrationSetting::findOrFail($integrationId);

        // Queue sync job
        Queue::push(function() use ($integration) {
            app('sync.engine')->scheduleSync($integration);
        });

        return [
            'integration_id' => $integrationId,
            'sync_scheduled' => true,
            'scheduled_at' => now()->toISOString()
        ];
    }

    /**
     * Execute notification step
     */
    private function executeNotificationStep(WorkflowStep $step): array
    {
        $config = $step->configuration;

        $event = $config['event'] ?? 'workflow.notification';
        $recipients = $config['recipients'] ?? [];
        $channels = $config['channels'] ?? ['email'];

        // Replace template variables with context data
        $message = $this->renderTemplate($config['message'] ?? [], $this->executionContext);

        $this->notificationService->send($event, [
            'workflow' => [
                'id' => $this->currentExecution->workflow_id,
                'execution_id' => $this->currentExecution->id
            ],
            'message' => $message,
            'context' => $this->executionContext
        ], $recipients, $channels);

        return [
            'notification_sent' => true,
            'recipients_count' => count($recipients),
            'channels' => $channels
        ];
    }

    /**
     * Execute data transformation step
     */
    private function executeTransformationStep(WorkflowStep $step): array
    {
        $config = $step->configuration;
        $transformations = $config['transformations'] ?? [];
        $results = [];

        foreach ($transformations as $transformation) {
            $sourceField = $transformation['source_field'] ?? '';
            $targetField = $transformation['target_field'] ?? '';
            $operation = $transformation['operation'] ?? 'copy';

            $sourceValue = data_get($this->executionContext, $sourceField);

            $transformedValue = match($operation) {
                'copy' => $sourceValue,
                'uppercase' => strtoupper($sourceValue),
                'lowercase' => strtolower($sourceValue),
                'trim' => trim($sourceValue),
                'format_date' => $this->formatDate($sourceValue, $transformation['format'] ?? 'Y-m-d'),
                'format_currency' => number_format($sourceValue, 2),
                'concat' => $sourceValue . ($transformation['suffix'] ?? ''),
                'replace' => str_replace($transformation['search'] ?? '', $transformation['replace'] ?? '', $sourceValue),
                'json_decode' => json_decode($sourceValue, true),
                'json_encode' => json_encode($sourceValue),
                default => $sourceValue
            };

            data_set($this->executionContext, $targetField, $transformedValue);
            $results[] = [
                'source_field' => $sourceField,
                'target_field' => $targetField,
                'operation' => $operation,
                'transformed' => true
            ];
        }

        return ['transformations' => $results];
    }

    /**
     * Execute conditional branch step
     */
    private function executeConditionalStep(WorkflowStep $step): array
    {
        $config = $step->configuration;
        $condition = $config['condition'] ?? [];
        $branchResult = $this->evaluateCondition($condition);

        // Store branch result in context for subsequent steps
        $this->executionContext['branch_results'][$step->id] = $branchResult;

        return [
            'condition_met' => $branchResult,
            'condition' => $condition
        ];
    }

    /**
     * Execute HTTP request step
     */
    private function executeHttpRequestStep(WorkflowStep $step): array
    {
        $config = $step->configuration;
        $client = new \GuzzleHttp\Client(['timeout' => 30]);

        $url = $this->renderTemplate($config['url'] ?? '', $this->executionContext);
        $method = $config['method'] ?? 'GET';
        $headers = $config['headers'] ?? [];
        $body = $this->renderTemplate($config['body'] ?? [], $this->executionContext);

        $response = $client->request($method, $url, [
            'headers' => $headers,
            'json' => $body
        ]);

        $responseData = json_decode($response->getBody(), true);

        // Store response in context
        $this->executionContext['http_responses'][$step->id] = $responseData;

        return [
            'status_code' => $response->getStatusCode(),
            'response_data' => $responseData,
            'url' => $url,
            'method' => $method
        ];
    }

    /**
     * Execute delay step
     */
    private function executeDelayStep(WorkflowStep $step): array
    {
        $config = $step->configuration;
        $delaySeconds = $config['delay_seconds'] ?? 60;

        sleep($delaySeconds);

        return [
            'delayed' => true,
            'delay_seconds' => $delaySeconds
        ];
    }

    /**
     * Execute approval gate step
     */
    private function executeApprovalStep(WorkflowStep $step): array
    {
        $config = $step->configuration;

        // Create approval request
        $approvalId = $this->createApprovalRequest($step, $config);

        // For now, auto-approve if configured
        if ($config['auto_approve'] ?? false) {
            return [
                'approval_status' => 'approved',
                'approval_id' => $approvalId,
                'auto_approved' => true
            ];
        }

        // In real implementation, this would wait for manual approval
        throw new Exception('Manual approval required - workflow paused');
    }

    /**
     * Execute custom script step
     */
    private function executeCustomScriptStep(WorkflowStep $step): array
    {
        $config = $step->configuration;
        $script = $config['script'] ?? '';

        if (empty($script)) {
            throw new Exception('No script provided for custom script step');
        }

        // For security, only allow pre-approved scripts
        $allowedScripts = config('workflow.allowed_scripts', []);

        if (!in_array($script, $allowedScripts)) {
            throw new Exception('Script not in allowed list');
        }

        // Execute the script (implement based on your security requirements)
        $result = $this->executeScript($script, $this->executionContext);

        return [
            'script_executed' => true,
            'script' => $script,
            'result' => $result
        ];
    }

    /**
     * Create workflow from template
     */
    public function createWorkflowFromTemplate(array $template): Workflow
    {
        return DB::transaction(function() use ($template) {
            $workflow = Workflow::create([
                'name' => $template['name'],
                'description' => $template['description'] ?? '',
                'trigger_event' => $template['trigger_event'],
                'trigger_conditions' => $template['trigger_conditions'] ?? [],
                'is_active' => $template['is_active'] ?? true,
                'created_by' => auth()->id()
            ]);

            foreach ($template['steps'] as $stepData) {
                WorkflowStep::create([
                    'workflow_id' => $workflow->id,
                    'name' => $stepData['name'],
                    'type' => $stepData['type'],
                    'configuration' => $stepData['configuration'] ?? [],
                    'conditions' => $stepData['conditions'] ?? [],
                    'order' => $stepData['order'],
                    'continue_on_failure' => $stepData['continue_on_failure'] ?? false
                ]);
            }

            return $workflow;
        });
    }

    /**
     * Get workflow execution statistics
     */
    public function getWorkflowStats(Workflow $workflow, int $days = 30): array
    {
        $since = now()->subDays($days);

        $executions = WorkflowExecution::where('workflow_id', $workflow->id)
            ->where('created_at', '>=', $since)
            ->get();

        $totalExecutions = $executions->count();
        $successfulExecutions = $executions->where('status', 'completed')->count();
        $failedExecutions = $executions->where('status', 'failed')->count();

        $successRate = $totalExecutions > 0 ? ($successfulExecutions / $totalExecutions) * 100 : 0;
        $avgExecutionTime = $executions->where('status', 'completed')
            ->map(fn($e) => $e->completed_at->diffInSeconds($e->started_at))
            ->avg();

        return [
            'total_executions' => $totalExecutions,
            'successful_executions' => $successfulExecutions,
            'failed_executions' => $failedExecutions,
            'success_rate' => round($successRate, 2),
            'average_execution_time' => round($avgExecutionTime ?? 0, 2),
            'executions_per_day' => round($totalExecutions / $days, 2)
        ];
    }

    // Helper methods
    private function renderTemplate($template, array $context): string|array
    {
        if (is_array($template)) {
            return array_map(fn($item) => $this->renderTemplate($item, $context), $template);
        }

        if (!is_string($template)) {
            return $template;
        }

        return preg_replace_callback('/\{\{([^}]+)\}\}/', function($matches) use ($context) {
            $field = trim($matches[1]);
            return data_get($context, $field, '');
        }, $template);
    }

    private function formatDate($date, string $format): string
    {
        return \Carbon\Carbon::parse($date)->format($format);
    }

    private function createApprovalRequest(WorkflowStep $step, array $config): string
    {
        // Implementation for creating approval requests
        return uniqid('approval_');
    }

    private function executeScript(string $script, array $context): array
    {
        // Secure script execution implementation
        return ['executed' => true];
    }
}
