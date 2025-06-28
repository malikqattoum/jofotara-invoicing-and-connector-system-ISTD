<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Workflow\WorkflowEngine;
use App\Models\Workflow;
use App\Models\WorkflowExecution;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WorkflowController extends Controller
{
    private $workflowEngine;

    public function __construct(WorkflowEngine $workflowEngine)
    {
        $this->workflowEngine = $workflowEngine;
    }

    /**
     * Display workflow management dashboard
     */
    public function index()
    {
        $workflows = Workflow::with(['executions' => function($query) {
            $query->latest()->take(5);
        }])->paginate(15);

        $stats = [
            'total_workflows' => Workflow::count(),
            'active_workflows' => Workflow::where('is_active', true)->count(),
            'total_executions' => WorkflowExecution::count(),
            'recent_executions' => WorkflowExecution::where('created_at', '>=', now()->subDays(7))->count()
        ];

        return view('admin.workflows.index', compact('workflows', 'stats'));
    }

    /**
     * Show workflow creation form
     */
    public function create()
    {
        $templates = $this->getWorkflowTemplates();
        $integrations = \App\Models\IntegrationSetting::where('status', 'active')->get();

        return view('admin.workflows.create', compact('templates', 'integrations'));
    }

    /**
     * Store new workflow
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_event' => 'required|string',
            'steps' => 'required|array|min:1',
            'steps.*.name' => 'required|string',
            'steps.*.type' => 'required|string',
            'steps.*.configuration' => 'required|array'
        ]);

        try {
            $workflow = $this->workflowEngine->createWorkflowFromTemplate([
                'name' => $request->name,
                'description' => $request->description,
                'trigger_event' => $request->trigger_event,
                'trigger_conditions' => $request->trigger_conditions ?? [],
                'steps' => $request->steps,
                'is_active' => $request->is_active ?? true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Workflow created successfully',
                'workflow' => $workflow
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create workflow: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show workflow details
     */
    public function show(Workflow $workflow)
    {
        $workflow->load(['steps', 'executions.workflow']);
        $stats = $this->workflowEngine->getWorkflowStats($workflow);

        return view('admin.workflows.show', compact('workflow', 'stats'));
    }

    /**
     * Execute workflow manually
     */
    public function execute(Request $request, Workflow $workflow): JsonResponse
    {
        try {
            $triggerData = $request->trigger_data ?? [];
            $execution = $this->workflowEngine->executeWorkflow($workflow, $triggerData);

            return response()->json([
                'success' => true,
                'message' => 'Workflow executed successfully',
                'execution' => $execution
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to execute workflow: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get workflow execution details
     */
    public function execution(WorkflowExecution $execution): JsonResponse
    {
        $execution->load('workflow');

        return response()->json([
            'execution' => $execution,
            'duration' => $execution->getDuration(),
            'status_class' => $this->getStatusClass($execution->status)
        ]);
    }

    /**
     * Get workflow analytics
     */
    public function analytics(Workflow $workflow): JsonResponse
    {
        $stats = $this->workflowEngine->getWorkflowStats($workflow, 30);

        return response()->json($stats);
    }

    /**
     * Toggle workflow status
     */
    public function toggle(Workflow $workflow): JsonResponse
    {
        $workflow->update(['is_active' => !$workflow->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Workflow status updated',
            'is_active' => $workflow->is_active
        ]);
    }

    /**
     * Delete workflow
     */
    public function destroy(Workflow $workflow): JsonResponse
    {
        try {
            $workflow->delete();

            return response()->json([
                'success' => true,
                'message' => 'Workflow deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete workflow: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get workflow templates
     */
    private function getWorkflowTemplates(): array
    {
        return [
            [
                'name' => 'Invoice Sync Workflow',
                'description' => 'Automatically sync invoices from integrations',
                'trigger_event' => 'invoice.created',
                'steps' => [
                    [
                        'name' => 'Sync Invoice Data',
                        'type' => 'sync_integration',
                        'configuration' => ['integration_id' => null],
                        'order' => 1
                    ],
                    [
                        'name' => 'Send Notification',
                        'type' => 'send_notification',
                        'configuration' => [
                            'event' => 'invoice.synced',
                            'channels' => ['email', 'slack'],
                            'message' => ['subject' => 'Invoice Synced', 'body' => 'Invoice {{invoice_number}} has been synced successfully']
                        ],
                        'order' => 2
                    ]
                ]
            ],
            [
                'name' => 'Data Quality Check',
                'description' => 'Check data quality and send alerts',
                'trigger_event' => 'data.quality.check',
                'steps' => [
                    [
                        'name' => 'Check Data Quality',
                        'type' => 'data_transformation',
                        'configuration' => [
                            'transformations' => [
                                ['source_field' => 'data.quality_score', 'target_field' => 'quality_score', 'operation' => 'copy']
                            ]
                        ],
                        'order' => 1
                    ],
                    [
                        'name' => 'Quality Gate Check',
                        'type' => 'conditional_branch',
                        'configuration' => [
                            'condition' => ['field' => 'quality_score', 'operator' => '<', 'value' => 80]
                        ],
                        'order' => 2
                    ],
                    [
                        'name' => 'Send Alert',
                        'type' => 'send_notification',
                        'configuration' => [
                            'event' => 'data.quality.alert',
                            'channels' => ['email', 'sms'],
                            'message' => ['subject' => 'Data Quality Alert', 'body' => 'Data quality score is {{quality_score}}%']
                        ],
                        'conditions' => [['field' => 'branch_results.2', 'operator' => '=', 'value' => true]],
                        'order' => 3
                    ]
                ]
            ]
        ];
    }

    /**
     * Get status CSS class
     */
    private function getStatusClass(string $status): string
    {
        return match($status) {
            'completed' => 'badge-success',
            'failed' => 'badge-danger',
            'running' => 'badge-warning',
            default => 'badge-secondary'
        };
    }
}
