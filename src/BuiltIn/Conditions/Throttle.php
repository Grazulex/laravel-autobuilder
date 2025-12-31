<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Conditions;

use Grazulex\AutoBuilder\Bricks\Condition;
use Grazulex\AutoBuilder\Fields\Number;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Illuminate\Support\Facades\Cache;

/**
 * Throttle Condition - Rate limit flow executions.
 *
 * Prevents flows from executing too frequently.
 * Returns true if within rate limit, false if throttled.
 */
class Throttle extends Condition
{
    public function name(): string
    {
        return 'Throttle';
    }

    public function description(): string
    {
        return 'Rate limit executions. Returns true if within limit, false if throttled.';
    }

    public function icon(): string
    {
        return 'gauge';
    }

    public function category(): string
    {
        return 'Flow Control';
    }

    public function fields(): array
    {
        return [
            Text::make('key')
                ->label('Throttle Key')
                ->description('Unique identifier for this throttle (supports variables)')
                ->placeholder('user_{{ user.id }}_email')
                ->supportsVariables()
                ->required(),

            Number::make('max_attempts')
                ->label('Max Attempts')
                ->description('Maximum number of allowed executions')
                ->default(5)
                ->min(1)
                ->required(),

            Number::make('decay_seconds')
                ->label('Time Window (seconds)')
                ->description('Time window for rate limiting')
                ->default(60)
                ->min(1)
                ->required(),

            Select::make('on_throttle')
                ->label('When Throttled')
                ->options([
                    'false' => 'Return false (continue to false branch)',
                    'skip' => 'Skip silently (return true but log)',
                ])
                ->default('false'),
        ];
    }

    public function evaluate(FlowContext $context): bool
    {
        $key = 'autobuilder:throttle:'.$this->resolveValue($this->config('key'), $context);
        $maxAttempts = (int) $this->config('max_attempts', 5);
        $decaySeconds = (int) $this->config('decay_seconds', 60);
        $onThrottle = $this->config('on_throttle', 'false');

        // Get current count
        $count = (int) Cache::get($key, 0);

        if ($count >= $maxAttempts) {
            $ttl = Cache::get($key.':ttl', 0);
            $remaining = max(0, $ttl - time());

            $context->log('warning', sprintf(
                'Throttle: Rate limited (%d/%d attempts). Reset in %ds. Key: %s',
                $count,
                $maxAttempts,
                $remaining,
                $key
            ));

            // Store throttle info in context
            $context->set('throttle_exceeded', true);
            $context->set('throttle_remaining_seconds', $remaining);
            $context->set('throttle_attempts', $count);
            $context->set('throttle_max', $maxAttempts);

            if ($onThrottle === 'skip') {
                return true; // Continue but we logged it
            }

            return false;
        }

        // Increment counter
        if ($count === 0) {
            Cache::put($key, 1, $decaySeconds);
            Cache::put($key.':ttl', time() + $decaySeconds, $decaySeconds);
        } else {
            Cache::increment($key);
        }

        $context->log('info', sprintf(
            'Throttle: %d/%d attempts used. Key: %s',
            $count + 1,
            $maxAttempts,
            $key
        ));

        $context->set('throttle_exceeded', false);
        $context->set('throttle_attempts', $count + 1);
        $context->set('throttle_max', $maxAttempts);
        $context->set('throttle_remaining', $maxAttempts - $count - 1);

        return true;
    }
}
