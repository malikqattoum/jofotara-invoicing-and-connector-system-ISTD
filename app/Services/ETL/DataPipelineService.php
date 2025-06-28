<?php

namespace App\Services\ETL;

use App\Models\DataPipeline;
use App\Models\PipelineExecution;
use App\Models\DataSource;
use App\Models\DataTransformation;
use App\Services\Analytics\AnalyticsDashboardService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Exception;

class DataPipelineService
{
    private $analyticsService;
    private $transformationRules = [];
    private $dataQualityRules = [];

    public function __construct(AnalyticsDashboardService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
        $this->loadTransformationRules();
        $this->loadDataQualityRules();
    }

    /**
     * Execute data pipeline
     */
    public function executePipeline(DataPipeline $pipeline): PipelineExecution
    {
        $execution = PipelineExecution::create([
            'pipeline_id' => $pipeline->id,
            'status' => 'running',
            'started_at' => now(),
            'metrics' => []
        ]);

        try {
            Log::info("Starting data pipeline execution", [
                'pipeline_id' => $pipeline->id,
                'execution_id' => $execution->id
            ]);

            $result = $this->processETLPipeline($pipeline, $execution);

            $execution->update([
                'status' => 'completed',
                'completed_at' => now(),
                'records_processed' => $result['records_processed'],
                'records_success' => $result['records_success'],
                'records_failed' => $result['records_failed'],
                'metrics' => $result['metrics']
            ]);

            Log::info("Data pipeline execution completed", [
                'pipeline_id' => $pipeline->id,
                'execution_id' => $execution->id,
                'records_processed' => $result['records_processed']
            ]);

        } catch (Exception $e) {
            $execution->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $e->getMessage()
            ]);

            Log::error("Data pipeline execution failed", [
                'pipeline_id' => $pipeline->id,
                'execution_id' => $execution->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }

        return $execution;
    }

    /**
     * Process ETL pipeline (Extract, Transform, Load)
     */
    private function processETLPipeline(DataPipeline $pipeline, PipelineExecution $execution): array
    {
        $metrics = [
            'extract_time' => 0,
            'transform_time' => 0,
            'load_time' => 0,
            'total_time' => 0,
            'data_quality_score' => 0
        ];

        $startTime = microtime(true);

        // Extract Phase
        $extractStart = microtime(true);
        $extractedData = $this->extractData($pipeline);
        $metrics['extract_time'] = (microtime(true) - $extractStart) * 1000;

        Log::info("Data extraction completed", [
            'pipeline_id' => $pipeline->id,
            'records_extracted' => $extractedData->count(),
            'extract_time' => $metrics['extract_time']
        ]);

        // Transform Phase
        $transformStart = microtime(true);
        $transformedData = $this->transformData($extractedData, $pipeline);
        $metrics['transform_time'] = (microtime(true) - $transformStart) * 1000;

        Log::info("Data transformation completed", [
            'pipeline_id' => $pipeline->id,
            'records_transformed' => $transformedData['success']->count(),
            'transform_time' => $metrics['transform_time']
        ]);

        // Data Quality Assessment
        $qualityScore = $this->assessDataQuality($transformedData['success']);
        $metrics['data_quality_score'] = $qualityScore;

        // Load Phase
        $loadStart = microtime(true);
        $loadResults = $this->loadData($transformedData['success'], $pipeline);
        $metrics['load_time'] = (microtime(true) - $loadStart) * 1000;

        $metrics['total_time'] = (microtime(true) - $startTime) * 1000;

        Log::info("Data loading completed", [
            'pipeline_id' => $pipeline->id,
            'records_loaded' => $loadResults['success_count'],
            'load_time' => $metrics['load_time']
        ]);

        return [
            'records_processed' => $extractedData->count(),
            'records_success' => $loadResults['success_count'],
            'records_failed' => $transformedData['failed']->count() + $loadResults['failed_count'],
            'metrics' => $metrics
        ];
    }

    /**
     * Extract data from configured sources
     */
    private function extractData(DataPipeline $pipeline): Collection
    {
        $allData = collect();

        foreach ($pipeline->data_sources as $sourceConfig) {
            $source = DataSource::find($sourceConfig['id']);

            if (!$source) {
                Log::warning("Data source not found", ['source_id' => $sourceConfig['id']]);
                continue;
            }

            $sourceData = $this->extractFromSource($source, $sourceConfig);
            $allData = $allData->concat($sourceData);
        }

        return $allData;
    }

    /**
     * Extract data from specific source
     */
    private function extractFromSource(DataSource $source, array $config): Collection
    {
        switch ($source->type) {
            case 'database':
                return $this->extractFromDatabase($source, $config);
            case 'api':
                return $this->extractFromAPI($source, $config);
            case 'file':
                return $this->extractFromFile($source, $config);
            case 'integration':
                return $this->extractFromIntegration($source, $config);
            default:
                throw new Exception("Unsupported data source type: {$source->type}");
        }
    }

    /**
     * Extract from database source
     */
    private function extractFromDatabase(DataSource $source, array $config): Collection
    {
        $connection = $source->connection_name ?? config('database.default');
        $query = $config['query'] ?? $source->configuration['query'];
        $bindings = $config['bindings'] ?? [];

        return collect(DB::connection($connection)->select($query, $bindings));
    }

    /**
     * Extract from API source
     */
    private function extractFromAPI(DataSource $source, array $config): Collection
    {
        $client = new \GuzzleHttp\Client(['timeout' => 60]);
        $url = $source->configuration['url'];
        $headers = $source->configuration['headers'] ?? [];
        $method = $source->configuration['method'] ?? 'GET';

        $response = $client->request($method, $url, [
            'headers' => $headers,
            'query' => $config['query_params'] ?? []
        ]);

        $data = json_decode($response->getBody(), true);

        // Extract nested data if configured
        $dataPath = $source->configuration['data_path'] ?? null;
        if ($dataPath) {
            $data = data_get($data, $dataPath, []);
        }

        return collect($data);
    }

    /**
     * Extract from file source
     */
    private function extractFromFile(DataSource $source, array $config): Collection
    {
        $filePath = $source->configuration['file_path'];
        $format = $source->configuration['format'] ?? 'csv';

        switch ($format) {
            case 'csv':
                return $this->extractFromCSV($filePath, $source->configuration);
            case 'json':
                return $this->extractFromJSON($filePath);
            case 'xml':
                return $this->extractFromXML($filePath, $source->configuration);
            default:
                throw new Exception("Unsupported file format: {$format}");
        }
    }

    /**
     * Extract from integration source
     */
    private function extractFromIntegration(DataSource $source, array $config): Collection
    {
        $integrationId = $source->configuration['integration_id'];
        $dataType = $config['data_type'] ?? 'invoices';
        $filters = $config['filters'] ?? [];

        // Use existing integration to fetch data
        $integration = \App\Models\IntegrationSetting::find($integrationId);
        if (!$integration) {
            throw new Exception("Integration not found: {$integrationId}");
        }

        $connector = app("connector.{$integration->vendor}");

        return match($dataType) {
            'invoices' => $connector->fetchInvoices($integration, $filters),
            'customers' => $connector->fetchCustomers($integration, $filters),
            default => collect()
        };
    }

    /**
     * Transform extracted data
     */
    private function transformData(Collection $data, DataPipeline $pipeline): array
    {
        $successfulRecords = collect();
        $failedRecords = collect();

        foreach ($data as $record) {
            try {
                $transformedRecord = $this->applyTransformations($record, $pipeline->transformations);

                // Validate transformed record
                if ($this->validateRecord($transformedRecord, $pipeline->validation_rules)) {
                    $successfulRecords->push($transformedRecord);
                } else {
                    $failedRecords->push([
                        'original' => $record,
                        'error' => 'Validation failed'
                    ]);
                }
            } catch (Exception $e) {
                $failedRecords->push([
                    'original' => $record,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'success' => $successfulRecords,
            'failed' => $failedRecords
        ];
    }

    /**
     * Apply transformations to a record
     */
    private function applyTransformations(array $record, array $transformations): array
    {
        $transformed = $record;

        foreach ($transformations as $transformation) {
            $transformed = $this->applyTransformation($transformed, $transformation);
        }

        return $transformed;
    }

    /**
     * Apply single transformation
     */
    private function applyTransformation(array $record, array $transformation): array
    {
        $type = $transformation['type'];
        $config = $transformation['config'] ?? [];

        return match($type) {
            'field_mapping' => $this->mapFields($record, $config),
            'data_type_conversion' => $this->convertDataTypes($record, $config),
            'data_cleansing' => $this->cleanseData($record, $config),
            'data_enrichment' => $this->enrichData($record, $config),
            'aggregation' => $this->aggregateData($record, $config),
            'filtering' => $this->filterData($record, $config),
            'custom_function' => $this->applyCustomFunction($record, $config),
            default => $record
        };
    }

    /**
     * Map fields according to configuration
     */
    private function mapFields(array $record, array $config): array
    {
        $mapped = [];
        $fieldMappings = $config['mappings'] ?? [];

        foreach ($fieldMappings as $mapping) {
            $sourceField = $mapping['source'];
            $targetField = $mapping['target'];
            $defaultValue = $mapping['default'] ?? null;

            $value = data_get($record, $sourceField, $defaultValue);
            data_set($mapped, $targetField, $value);
        }

        // Include unmapped fields if configured
        if ($config['include_unmapped'] ?? false) {
            $mappedSources = array_column($fieldMappings, 'source');
            foreach ($record as $key => $value) {
                if (!in_array($key, $mappedSources)) {
                    $mapped[$key] = $value;
                }
            }
        }

        return $mapped;
    }

    /**
     * Convert data types
     */
    private function convertDataTypes(array $record, array $config): array
    {
        $conversions = $config['conversions'] ?? [];

        foreach ($conversions as $conversion) {
            $field = $conversion['field'];
            $fromType = $conversion['from_type'];
            $toType = $conversion['to_type'];

            if (isset($record[$field])) {
                $record[$field] = $this->convertValue($record[$field], $fromType, $toType);
            }
        }

        return $record;
    }

    /**
     * Cleanse data
     */
    private function cleanseData(array $record, array $config): array
    {
        $rules = $config['rules'] ?? [];

        foreach ($rules as $rule) {
            $field = $rule['field'];
            $operations = $rule['operations'] ?? [];

            if (isset($record[$field])) {
                foreach ($operations as $operation) {
                    $record[$field] = $this->applyCleansingOperation($record[$field], $operation);
                }
            }
        }

        return $record;
    }

    /**
     * Assess data quality
     */
    private function assessDataQuality(Collection $data): float
    {
        if ($data->isEmpty()) {
            return 0;
        }

        $totalScore = 0;
        $ruleCount = count($this->dataQualityRules);

        foreach ($this->dataQualityRules as $rule) {
            $score = $this->evaluateQualityRule($data, $rule);
            $totalScore += $score;
        }

        return $ruleCount > 0 ? ($totalScore / $ruleCount) * 100 : 100;
    }

    /**
     * Load transformed data to destination
     */
    private function loadData(Collection $data, DataPipeline $pipeline): array
    {
        $successCount = 0;
        $failedCount = 0;
        $batchSize = $pipeline->configuration['batch_size'] ?? 1000;

        $chunks = $data->chunk($batchSize);

        foreach ($chunks as $chunk) {
            try {
                $this->loadDataChunk($chunk, $pipeline);
                $successCount += $chunk->count();
            } catch (Exception $e) {
                Log::error("Failed to load data chunk", [
                    'pipeline_id' => $pipeline->id,
                    'chunk_size' => $chunk->count(),
                    'error' => $e->getMessage()
                ]);
                $failedCount += $chunk->count();
            }
        }

        return [
            'success_count' => $successCount,
            'failed_count' => $failedCount
        ];
    }

    /**
     * Load data chunk to destination
     */
    private function loadDataChunk(Collection $chunk, DataPipeline $pipeline): void
    {
        $destination = $pipeline->destination;

        switch ($destination['type']) {
            case 'database':
                $this->loadToDatabase($chunk, $destination);
                break;
            case 'api':
                $this->loadToAPI($chunk, $destination);
                break;
            case 'file':
                $this->loadToFile($chunk, $destination);
                break;
            case 'cache':
                $this->loadToCache($chunk, $destination);
                break;
            default:
                throw new Exception("Unsupported destination type: {$destination['type']}");
        }
    }

    /**
     * Create data pipeline from configuration
     */
    public function createPipeline(array $config): DataPipeline
    {
        return DataPipeline::create([
            'name' => $config['name'],
            'description' => $config['description'] ?? '',
            'data_sources' => $config['data_sources'],
            'transformations' => $config['transformations'] ?? [],
            'validation_rules' => $config['validation_rules'] ?? [],
            'destination' => $config['destination'],
            'schedule' => $config['schedule'] ?? null,
            'configuration' => $config['configuration'] ?? [],
            'is_active' => $config['is_active'] ?? true,
            'created_by' => auth()->id()
        ]);
    }

    /**
     * Get pipeline statistics
     */
    public function getPipelineStats(DataPipeline $pipeline, int $days = 30): array
    {
        $since = now()->subDays($days);

        $executions = PipelineExecution::where('pipeline_id', $pipeline->id)
            ->where('created_at', '>=', $since)
            ->get();

        $totalExecutions = $executions->count();
        $successfulExecutions = $executions->where('status', 'completed')->count();
        $avgProcessingTime = $executions->where('status', 'completed')
            ->avg(fn($e) => data_get($e->metrics, 'total_time', 0));
        $avgDataQualityScore = $executions->where('status', 'completed')
            ->avg(fn($e) => data_get($e->metrics, 'data_quality_score', 0));

        return [
            'total_executions' => $totalExecutions,
            'successful_executions' => $successfulExecutions,
            'success_rate' => $totalExecutions > 0 ? ($successfulExecutions / $totalExecutions) * 100 : 0,
            'avg_processing_time' => round($avgProcessingTime ?? 0, 2),
            'avg_data_quality_score' => round($avgDataQualityScore ?? 0, 2),
            'total_records_processed' => $executions->sum('records_processed'),
            'data_quality_trend' => $this->getDataQualityTrend($executions)
        ];
    }

    // Helper methods
    private function loadTransformationRules(): void
    {
        $this->transformationRules = config('etl.transformation_rules', []);
    }

    private function loadDataQualityRules(): void
    {
        $this->dataQualityRules = config('etl.data_quality_rules', [
            'completeness' => ['weight' => 0.3],
            'accuracy' => ['weight' => 0.3],
            'consistency' => ['weight' => 0.2],
            'validity' => ['weight' => 0.2]
        ]);
    }

    private function extractFromCSV(string $filePath, array $config): Collection
    {
        $delimiter = $config['delimiter'] ?? ',';
        $hasHeader = $config['has_header'] ?? true;

        $data = collect();
        $handle = fopen($filePath, 'r');

        if ($hasHeader) {
            $headers = fgetcsv($handle, 0, $delimiter);
        }

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($hasHeader) {
                $data->push(array_combine($headers, $row));
            } else {
                $data->push($row);
            }
        }

        fclose($handle);
        return $data;
    }

    private function extractFromJSON(string $filePath): Collection
    {
        $content = file_get_contents($filePath);
        $data = json_decode($content, true);
        return collect($data);
    }

    private function extractFromXML(string $filePath, array $config): Collection
    {
        $xml = simplexml_load_file($filePath);
        $array = json_decode(json_encode($xml), true);
        return collect($array);
    }

    private function validateRecord(array $record, array $rules): bool
    {
        foreach ($rules as $rule) {
            if (!$this->evaluateValidationRule($record, $rule)) {
                return false;
            }
        }
        return true;
    }

    private function convertValue($value, string $fromType, string $toType)
    {
        return match($toType) {
            'string' => (string) $value,
            'integer' => (int) $value,
            'float' => (float) $value,
            'boolean' => (bool) $value,
            'date' => \Carbon\Carbon::parse($value)->format('Y-m-d'),
            'datetime' => \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s'),
            default => $value
        };
    }

    private function applyCleansingOperation($value, array $operation)
    {
        return match($operation['type']) {
            'trim' => trim($value),
            'uppercase' => strtoupper($value),
            'lowercase' => strtolower($value),
            'remove_special_chars' => preg_replace('/[^A-Za-z0-9\s]/', '', $value),
            'normalize_phone' => preg_replace('/[^0-9]/', '', $value),
            'normalize_email' => strtolower(trim($value)),
            default => $value
        };
    }

    private function evaluateQualityRule(Collection $data, array $rule): float
    {
        // Implementation for specific quality rules
        return 85.0; // Placeholder
    }

    private function evaluateValidationRule(array $record, array $rule): bool
    {
        // Implementation for validation rules
        return true; // Placeholder
    }

    private function enrichData(array $record, array $config): array { return $record; }
    private function aggregateData(array $record, array $config): array { return $record; }
    private function filterData(array $record, array $config): array { return $record; }
    private function applyCustomFunction(array $record, array $config): array { return $record; }
    private function loadToDatabase(Collection $chunk, array $destination): void { /* Implementation */ }
    private function loadToAPI(Collection $chunk, array $destination): void { /* Implementation */ }
    private function loadToFile(Collection $chunk, array $destination): void { /* Implementation */ }
    private function loadToCache(Collection $chunk, array $destination): void { /* Implementation */ }
    private function getDataQualityTrend($executions): array { return []; }
}
