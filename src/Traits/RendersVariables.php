<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Traits;

use Carbon\Carbon;
use Grazulex\AutoBuilder\Flow\FlowContext;

trait RendersVariables
{
    /**
     * Render a template string with context variables
     */
    protected function render(string $template, FlowContext $context): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*(.+?)\s*\}\}/',
            function ($matches) use ($context) {
                $expression = trim($matches[1]);

                // Support filters: {{ user.name | upper }}
                if (str_contains($expression, '|')) {
                    [$key, $filter] = array_map('trim', explode('|', $expression, 2));
                    $value = $context->get($key, '');

                    return $this->applyFilter($value, $filter);
                }

                return (string) $context->get($expression, '');
            },
            $template
        );
    }

    /**
     * Apply a filter to a value
     */
    protected function applyFilter(mixed $value, string $filter): mixed
    {
        return match ($filter) {
            'upper' => strtoupper((string) $value),
            'lower' => strtolower((string) $value),
            'ucfirst' => ucfirst((string) $value),
            'ucwords' => ucwords((string) $value),
            'trim' => trim((string) $value),
            'json' => json_encode($value),
            'date' => $this->formatDate($value, 'Y-m-d'),
            'datetime' => $this->formatDate($value, 'Y-m-d H:i:s'),
            'time' => $this->formatDate($value, 'H:i:s'),
            'count' => is_array($value) ? count($value) : strlen((string) $value),
            'first' => is_array($value) ? ($value[0] ?? '') : $value,
            'last' => is_array($value) ? (end($value) ?: '') : $value,
            'join' => is_array($value) ? implode(', ', $value) : $value,
            'keys' => is_array($value) ? array_keys($value) : [],
            'values' => is_array($value) ? array_values($value) : [],
            'reverse' => is_array($value) ? array_reverse($value) : strrev((string) $value),
            'sort' => is_array($value) ? tap($value, fn (&$v) => sort($v)) : $value,
            'unique' => is_array($value) ? array_unique($value) : $value,
            'default' => $value ?: '',
            default => $value,
        };
    }

    /**
     * Format a date value
     */
    protected function formatDate(mixed $value, string $format): string
    {
        if ($value instanceof Carbon) {
            return $value->format($format);
        }

        if (is_string($value) || is_numeric($value)) {
            return Carbon::parse($value)->format($format);
        }

        return '';
    }

    /**
     * Resolve a value that may contain variables
     */
    protected function resolveValue(mixed $value, FlowContext $context): mixed
    {
        if (is_string($value)) {
            // Check if it's a simple variable reference
            if (preg_match('/^\{\{\s*(.+?)\s*\}\}$/', $value, $matches)) {
                return $context->get(trim($matches[1]));
            }

            // Otherwise render as template
            return $this->render($value, $context);
        }

        if (is_array($value)) {
            return array_map(fn ($v) => $this->resolveValue($v, $context), $value);
        }

        return $value;
    }

    /**
     * Resolve key-value pairs with variable support
     */
    protected function resolveKeyValue(array $data, FlowContext $context): array
    {
        $resolved = [];

        foreach ($data as $key => $value) {
            $resolvedKey = is_string($key) ? $this->render($key, $context) : $key;
            $resolved[$resolvedKey] = $this->resolveValue($value, $context);
        }

        return $resolved;
    }
}
