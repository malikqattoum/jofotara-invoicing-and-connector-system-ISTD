<?php

namespace App\Services;

use App\Models\IntegrationSetting;
use App\Models\DataMapping;
use Illuminate\Support\Collection;

class DataMappingService
{
    public function createMapping(IntegrationSetting $integration, array $mappingData): DataMapping
    {
        return DataMapping::create([
            'integration_setting_id' => $integration->id,
            'data_type' => $mappingData['data_type'],
            'source_field' => $mappingData['source_field'],
            'target_field' => $mappingData['target_field'],
            'transformation_rules' => $mappingData['transformation_rules'] ?? [],
            'is_required' => $mappingData['is_required'] ?? false,
            'default_value' => $mappingData['default_value'] ?? null
        ]);
    }

    public function getMappings(IntegrationSetting $integration, string $dataType): Collection
    {
        return DataMapping::where('integration_setting_id', $integration->id)
            ->where('data_type', $dataType)
            ->get();
    }

    public function applyMappings(IntegrationSetting $integration, string $dataType, array $sourceData): array
    {
        $mappings = $this->getMappings($integration, $dataType);
        $mappedData = [];

        foreach ($mappings as $mapping) {
            $sourceValue = $this->getSourceValue($sourceData, $mapping->source_field);
            $transformedValue = $this->applyTransformations($sourceValue, $mapping->transformation_rules);

            $this->setTargetValue($mappedData, $mapping->target_field, $transformedValue);
        }

        return $mappedData;
    }

    public function reverseMapping(IntegrationSetting $integration, string $dataType, array $targetData): array
    {
        $mappings = $this->getMappings($integration, $dataType);
        $sourceData = [];

        foreach ($mappings as $mapping) {
            $targetValue = $this->getSourceValue($targetData, $mapping->target_field);
            $reversedValue = $this->reverseTransformations($targetValue, $mapping->transformation_rules);

            $this->setTargetValue($sourceData, $mapping->source_field, $reversedValue);
        }

        return $sourceData;
    }

    protected function getSourceValue(array $data, string $fieldPath)
    {
        $keys = explode('.', $fieldPath);
        $value = $data;

        foreach ($keys as $key) {
            if (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } else {
                return null;
            }
        }

        return $value;
    }

    protected function setTargetValue(array &$data, string $fieldPath, $value): void
    {
        $keys = explode('.', $fieldPath);
        $current = &$data;

        for ($i = 0; $i < count($keys) - 1; $i++) {
            $key = $keys[$i];
            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }

        $current[end($keys)] = $value;
    }

    protected function applyTransformations($value, array $rules)
    {
        foreach ($rules as $rule) {
            $value = $this->applyTransformation($value, $rule);
        }

        return $value;
    }

    protected function applyTransformation($value, array $rule)
    {
        $type = $rule['type'] ?? 'none';

        return match ($type) {
            'uppercase' => strtoupper($value),
            'lowercase' => strtolower($value),
            'trim' => trim($value),
            'date_format' => $this->formatDate($value, $rule['from_format'] ?? 'Y-m-d', $rule['to_format'] ?? 'Y-m-d'),
            'concatenate' => $this->concatenateValues($value, $rule['values'] ?? [], $rule['separator'] ?? ''),
            'replace' => str_replace($rule['search'] ?? '', $rule['replace'] ?? '', $value),
            'regex_replace' => preg_replace($rule['pattern'] ?? '', $rule['replacement'] ?? '', $value),
            'number_format' => number_format($value, $rule['decimals'] ?? 2),
            'currency_format' => $this->formatCurrency($value, $rule['currency'] ?? 'USD'),
            'default_if_empty' => empty($value) ? ($rule['default'] ?? null) : $value,
            'mapping' => $this->applyValueMapping($value, $rule['map'] ?? []),
            default => $value
        };
    }

    protected function reverseTransformations($value, array $rules)
    {
        // Apply transformations in reverse order
        $reversedRules = array_reverse($rules);

        foreach ($reversedRules as $rule) {
            $value = $this->reverseTransformation($value, $rule);
        }

        return $value;
    }

    protected function reverseTransformation($value, array $rule)
    {
        $type = $rule['type'] ?? 'none';

        return match ($type) {
            'date_format' => $this->formatDate($value, $rule['to_format'] ?? 'Y-m-d', $rule['from_format'] ?? 'Y-m-d'),
            'mapping' => $this->reverseValueMapping($value, $rule['map'] ?? []),
            // Most transformations are not easily reversible
            default => $value
        };
    }

    protected function formatDate($value, string $fromFormat, string $toFormat)
    {
        if (!$value) return null;

        try {
            $date = \DateTime::createFromFormat($fromFormat, $value);
            return $date ? $date->format($toFormat) : $value;
        } catch (\Exception $e) {
            return $value;
        }
    }

    protected function concatenateValues($baseValue, array $additionalValues, string $separator): string
    {
        $values = array_filter(array_merge([$baseValue], $additionalValues));
        return implode($separator, $values);
    }

    protected function formatCurrency($value, string $currency): string
    {
        if (!is_numeric($value)) return $value;

        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥'
        ];

        $symbol = $symbols[$currency] ?? $currency;
        return $symbol . number_format($value, 2);
    }

    protected function applyValueMapping($value, array $map)
    {
        return $map[$value] ?? $value;
    }

    protected function reverseValueMapping($value, array $map)
    {
        $reversedMap = array_flip($map);
        return $reversedMap[$value] ?? $value;
    }

    public function getAvailableTransformations(): array
    {
        return [
            'uppercase' => [
                'name' => 'Uppercase',
                'description' => 'Convert text to uppercase',
                'parameters' => []
            ],
            'lowercase' => [
                'name' => 'Lowercase',
                'description' => 'Convert text to lowercase',
                'parameters' => []
            ],
            'trim' => [
                'name' => 'Trim',
                'description' => 'Remove whitespace from beginning and end',
                'parameters' => []
            ],
            'date_format' => [
                'name' => 'Date Format',
                'description' => 'Convert date from one format to another',
                'parameters' => ['from_format', 'to_format']
            ],
            'concatenate' => [
                'name' => 'Concatenate',
                'description' => 'Join multiple values together',
                'parameters' => ['values', 'separator']
            ],
            'replace' => [
                'name' => 'Replace',
                'description' => 'Replace text with another text',
                'parameters' => ['search', 'replace']
            ],
            'regex_replace' => [
                'name' => 'Regex Replace',
                'description' => 'Replace text using regular expressions',
                'parameters' => ['pattern', 'replacement']
            ],
            'number_format' => [
                'name' => 'Number Format',
                'description' => 'Format numbers with decimals',
                'parameters' => ['decimals']
            ],
            'currency_format' => [
                'name' => 'Currency Format',
                'description' => 'Format as currency',
                'parameters' => ['currency']
            ],
            'default_if_empty' => [
                'name' => 'Default If Empty',
                'description' => 'Use default value if field is empty',
                'parameters' => ['default']
            ],
            'mapping' => [
                'name' => 'Value Mapping',
                'description' => 'Map values to other values',
                'parameters' => ['map']
            ]
        ];
    }
}
