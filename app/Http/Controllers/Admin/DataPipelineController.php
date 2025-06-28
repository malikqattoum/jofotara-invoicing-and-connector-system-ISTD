<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ETL\DataPipelineService;
use App\Models\DataPipeline;
use App\Models\DataSource;
use App\Models\PipelineExecution;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DataPipelineController extends Controller
{
    private $pipelineService;

    public function __construct(DataPipelineService $pipelineService)
    {
        $this->pipelineService = $pipelineService;
    }

    /**
     * Display data pipeline dashboard
     */
    public function index()
    {
        $pipelines = DataPipeline::with(['executions' => function($query) {
            $query->latest()->take(3);
        }])->paginate(15);

        $stats = [
            'total_pipelines' => DataPipeline::count(),
            'active_pipelines' => DataPipeline::where('is_active', true)->count(),
            'total_executions' => PipelineExecution::count(),
            'data_sources' => DataSource::count()
        ];

        return view('admin.pipelines.index', compact('pipelines', 'stats'));
    }

    /**
     * Show pipeline creation form
     */
    public function create()
    {
        $dataSources = DataSource::where('is_active', true)->get();
        $integrations = \App\Models\IntegrationSetting::where('status', 'active')->get();

        return view('admin.pipelines.create', compact('dataSources', 'integrations'));
    }

    /**
     * Store new pipeline
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'data_sources' => 'required|array|min:1',
            'transformations' => 'nullable|array',
            'destination' => 'required|array'
        ]);

        try {
            $pipeline = $this->pipelineService->createPipeline($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Data pipeline created successfully',
                'pipeline' => $pipeline
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create pipeline: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show pipeline details
     */
    public function show(DataPipeline $pipeline)
    {
        $pipeline->load('executions');
        $stats = $this->pipelineService->getPipelineStats($pipeline);

        return view('admin.pipelines.show', compact('pipeline', 'stats'));
    }

    /**
     * Execute pipeline manually
     */
    public function execute(DataPipeline $pipeline): JsonResponse
    {
        try {
            $execution = $this->pipelineService->executePipeline($pipeline);

            return response()->json([
                'success' => true,
                'message' => 'Pipeline executed successfully',
                'execution' => $execution
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to execute pipeline: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pipeline analytics
     */
    public function analytics(DataPipeline $pipeline): JsonResponse
    {
        $stats = $this->pipelineService->getPipelineStats($pipeline, 30);

        return response()->json($stats);
    }

    /**
     * Toggle pipeline status
     */
    public function toggle(DataPipeline $pipeline): JsonResponse
    {
        $pipeline->update(['is_active' => !$pipeline->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Pipeline status updated',
            'is_active' => $pipeline->is_active
        ]);
    }
}
