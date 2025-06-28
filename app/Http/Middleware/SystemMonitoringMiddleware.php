<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\Monitoring\PerformanceMonitoringService;
use App\Services\Security\SecurityAuditService;
use App\Services\API\APIGatewayService;
use App\Services\EventStreaming\EventStreamingService;

class SystemMonitoringMiddleware
{
    private $performanceService;
    private $securityService;
    private $apiGatewayService;
    private $eventStreamingService;

    public function __construct(
        PerformanceMonitoringService $performanceService,
        SecurityAuditService $securityService,
        APIGatewayService $apiGatewayService,
        EventStreamingService $eventStreamingService
    ) {
        $this->performanceService = $performanceService;
        $this->securityService = $securityService;
        $this->apiGatewayService = $apiGatewayService;
        $this->eventStreamingService = $eventStreamingService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);

        // Security audit logging
        $this->securityService->logSecurityEvent('request.started', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'user_id' => auth()->id()
        ]);

        // Check if this is an API request
        if ($request->is('api/*')) {
            // Process through API Gateway
            try {
                $response = $this->apiGatewayService->processRequest($request);

                // Record performance metrics
                $responseTime = (microtime(true) - $startTime) * 1000;
                $this->performanceService->recordMetric('api_response_time', $responseTime, [
                    'endpoint' => $request->path(),
                    'method' => $request->method(),
                    'status_code' => $response->getStatusCode()
                ]);

                return $response;

            } catch (\Exception $e) {
                // Record API Gateway failure
                $this->performanceService->recordMetric('api_error', 1, [
                    'endpoint' => $request->path(),
                    'error' => $e->getMessage()
                ]);

                throw $e;
            }
        }

        // Process regular web requests
        $response = $next($request);

        // Record performance metrics
        $responseTime = (microtime(true) - $startTime) * 1000;
        $this->performanceService->recordMetric('web_response_time', $responseTime, [
            'route' => $request->route() ? $request->route()->getName() : 'unknown',
            'status_code' => $response->getStatusCode()
        ]);

        // Publish events for significant actions
        if ($this->isSignificantAction($request)) {
            $this->eventStreamingService->publishEvent('system.action', [
                'type' => 'web_request',
                'action' => $request->route() ? $request->route()->getName() : 'unknown',
                'user_id' => auth()->id(),
                'response_time' => $responseTime,
                'status_code' => $response->getStatusCode()
            ]);
        }

        return $response;
    }

    /**
     * Check if this is a significant action worth logging
     */
    private function isSignificantAction(Request $request): bool
    {
        $significantRoutes = [
            'invoices.store',
            'invoices.update',
            'invoices.destroy',
            'integrations.sync',
            'workflows.execute',
            'pipelines.execute'
        ];

        $routeName = $request->route() ? $request->route()->getName() : '';

        return in_array($routeName, $significantRoutes) ||
               $request->isMethod('POST') ||
               $request->isMethod('PUT') ||
               $request->isMethod('DELETE');
    }
}
