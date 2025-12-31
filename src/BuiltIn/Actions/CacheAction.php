<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Actions;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Fields\Number;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Fields\Textarea;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Illuminate\Support\Facades\Cache;

/**
 * Cache Action - Interact with Laravel's cache system.
 *
 * Supports put, get, forget, has, increment, and decrement operations.
 */
class CacheAction extends Action
{
    public function name(): string
    {
        return 'Cache';
    }

    public function description(): string
    {
        return 'Store, retrieve, or delete cached values.';
    }

    public function icon(): string
    {
        return 'database';
    }

    public function category(): string
    {
        return 'Data';
    }

    public function fields(): array
    {
        return [
            Select::make('operation')
                ->label('Operation')
                ->options([
                    'put' => 'Put (store value)',
                    'get' => 'Get (retrieve value)',
                    'forget' => 'Forget (delete)',
                    'has' => 'Has (check exists)',
                    'increment' => 'Increment',
                    'decrement' => 'Decrement',
                ])
                ->default('put')
                ->required(),

            Text::make('key')
                ->label('Cache Key')
                ->description('The key to store/retrieve the value')
                ->supportsVariables()
                ->required(),

            Textarea::make('value')
                ->label('Value')
                ->description('The value to cache (supports variables)')
                ->supportsVariables()
                ->visibleWhen('operation', 'put'),

            Number::make('ttl')
                ->label('TTL (seconds)')
                ->description('Time to live in seconds (0 = forever)')
                ->default(3600)
                ->min(0)
                ->visibleWhen('operation', 'put'),

            Number::make('amount')
                ->label('Amount')
                ->description('Amount to increment/decrement')
                ->default(1)
                ->min(1),

            Text::make('store_as')
                ->label('Store Result As')
                ->description('Variable name to store the result')
                ->default('cache_result'),

            Select::make('store')
                ->label('Cache Store')
                ->options([
                    '' => 'Default',
                    'file' => 'File',
                    'redis' => 'Redis',
                    'memcached' => 'Memcached',
                    'database' => 'Database',
                    'array' => 'Array (in-memory)',
                ])
                ->default(''),
        ];
    }

    public function handle(FlowContext $context): FlowContext
    {
        $operation = $this->config('operation', 'put');
        $key = $this->resolveValue($this->config('key'), $context);
        $storeAs = $this->config('store_as', 'cache_result');
        $storeName = $this->config('store', '');

        $cache = $storeName ? Cache::store($storeName) : Cache::getFacadeRoot();

        $result = match ($operation) {
            'put' => $this->handlePut($cache, $key, $context),
            'get' => $this->handleGet($cache, $key, $context),
            'forget' => $this->handleForget($cache, $key, $context),
            'has' => $this->handleHas($cache, $key, $context),
            'increment' => $this->handleIncrement($cache, $key, $context),
            'decrement' => $this->handleDecrement($cache, $key, $context),
            default => null,
        };

        $context->set($storeAs, $result);
        $context->log('info', "Cache {$operation}: {$key}");

        return $context;
    }

    private function handlePut($cache, string $key, FlowContext $context): bool
    {
        $value = $this->resolveValue($this->config('value', ''), $context);
        $ttl = (int) $this->config('ttl', 3600);

        // Try to decode JSON if it looks like JSON
        if (is_string($value) && (str_starts_with($value, '{') || str_starts_with($value, '['))) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        if ($ttl > 0) {
            return $cache->put($key, $value, $ttl);
        }

        return $cache->forever($key, $value);
    }

    private function handleGet($cache, string $key, FlowContext $context): mixed
    {
        return $cache->get($key);
    }

    private function handleForget($cache, string $key, FlowContext $context): bool
    {
        return $cache->forget($key);
    }

    private function handleHas($cache, string $key, FlowContext $context): bool
    {
        return $cache->has($key);
    }

    private function handleIncrement($cache, string $key, FlowContext $context): int|bool
    {
        $amount = (int) $this->config('amount', 1);

        return $cache->increment($key, $amount);
    }

    private function handleDecrement($cache, string $key, FlowContext $context): int|bool
    {
        $amount = (int) $this->config('amount', 1);

        return $cache->decrement($key, $amount);
    }
}
