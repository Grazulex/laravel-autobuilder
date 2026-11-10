<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\SetVariable;
use Grazulex\AutoBuilder\BuiltIn\Triggers\OnManualTrigger;
use Grazulex\AutoBuilder\Events\BrickFailed;
use Grazulex\AutoBuilder\Flow\FlowRunner;
use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Registry\BrickRegistry;
use Grazulex\AutoBuilder\Tests\Fixtures\Bricks\CustomRetryFlakyAction;
use Grazulex\AutoBuilder\Tests\Fixtures\Bricks\FlakyAction;
use Grazulex\AutoBuilder\Tests\Fixtures\Bricks\NonRetryableFlakyAction;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Sleep;

beforeEach(function () {
    Sleep::fake();

    $this->registry = app(BrickRegistry::class);
    $this->registry->discover();
    $this->runner = new FlowRunner($this->registry);

    $this->makeFlow = fn (string $brick, array $config, array $extraNodes = [], array $extraEdges = []): Flow => Flow::create([
        'name' => 'Retry Flow',
        'nodes' => array_merge([
            [
                'id' => 'trigger-1',
                'type' => 'trigger',
                'brick' => OnManualTrigger::class,
                'config' => [],
            ],
            [
                'id' => 'action-1',
                'type' => 'action',
                'brick' => $brick,
                'config' => $config,
            ],
        ], $extraNodes),
        'edges' => array_merge([
            ['source' => 'trigger-1', 'target' => 'action-1'],
        ], $extraEdges),
        'is_active' => true,
    ]);
});

function retryWarnings(array $logs): array
{
    return array_values(array_filter(
        $logs,
        fn (array $log): bool => $log['level'] === 'warning' && str_contains($log['message'], 'Retrying in')
    ));
}

it('retries a failing action using the global configuration', function () {
    config([
        'autobuilder.execution.retry_attempts' => 3,
        'autobuilder.execution.retry_delay' => 7,
    ]);

    $flow = ($this->makeFlow)(FlakyAction::class, ['fail_times' => 2]);

    $result = $this->runner->run($flow);

    expect($result->status)->toBe('completed');
    expect($result->context->get('flaky_attempts'))->toBe(3);
    expect(retryWarnings($result->context->logs))->toHaveCount(2);

    Sleep::assertSleptTimes(2);
    Sleep::assertSequence([
        Sleep::for(7)->seconds(),
        Sleep::for(7)->seconds(),
    ]);
});

it('fails the flow once retry attempts are exhausted', function () {
    Event::fake([BrickFailed::class]);

    config([
        'autobuilder.execution.retry_attempts' => 2,
        'autobuilder.execution.retry_delay' => 1,
    ]);

    $flow = ($this->makeFlow)(FlakyAction::class, ['fail_times' => 10]);

    $result = $this->runner->run($flow);

    expect($result->status)->toBe('failed');
    expect($result->error?->getMessage())->toBe('Flaky failure #3');
    expect($result->context->get('flaky_attempts'))->toBe(3);

    Sleep::assertSleptTimes(2);

    // Retried attempts do not emit BrickFailed: only the final failure does, once for the action.
    $actionFailures = Event::dispatched(
        BrickFailed::class,
        fn (BrickFailed $event): bool => $event->brick instanceof FlakyAction
    );

    expect($actionFailures)->toHaveCount(1);
    expect($actionFailures->first()[0]->exception->getMessage())->toBe('Flaky failure #3');
});

it('does not retry when retry_attempts is zero', function () {
    config(['autobuilder.execution.retry_attempts' => 0]);

    $flow = ($this->makeFlow)(FlakyAction::class, ['fail_times' => 1]);

    $result = $this->runner->run($flow);

    expect($result->status)->toBe('failed');
    expect($result->context->get('flaky_attempts'))->toBe(1);

    Sleep::assertNeverSlept();
});

it('lets the node configuration override the global retry settings', function () {
    config([
        'autobuilder.execution.retry_attempts' => 0,
        'autobuilder.execution.retry_delay' => 60,
    ]);

    $flow = ($this->makeFlow)(FlakyAction::class, [
        'fail_times' => 1,
        'retry_attempts' => 1,
        'retry_delay' => 2,
    ]);

    $result = $this->runner->run($flow);

    expect($result->status)->toBe('completed');
    expect($result->context->get('flaky_attempts'))->toBe(2);

    Sleep::assertSequence([Sleep::for(2)->seconds()]);
});

it('lets the node configuration disable retries', function () {
    config(['autobuilder.execution.retry_attempts' => 3]);

    $flow = ($this->makeFlow)(FlakyAction::class, [
        'fail_times' => 1,
        'retry_attempts' => 0,
    ]);

    $result = $this->runner->run($flow);

    expect($result->status)->toBe('failed');
    expect($result->context->get('flaky_attempts'))->toBe(1);

    Sleep::assertNeverSlept();
});

it('does not sleep when the retry delay is zero', function () {
    $flow = ($this->makeFlow)(FlakyAction::class, [
        'fail_times' => 1,
        'retry_attempts' => 1,
        'retry_delay' => 0,
    ]);

    $result = $this->runner->run($flow);

    expect($result->status)->toBe('completed');
    expect($result->context->get('flaky_attempts'))->toBe(2);

    Sleep::assertNeverSlept();
});

it('uses the retry settings defined by the action class', function () {
    config([
        'autobuilder.execution.retry_attempts' => 5,
        'autobuilder.execution.retry_delay' => 60,
    ]);

    $flow = ($this->makeFlow)(CustomRetryFlakyAction::class, ['fail_times' => 5]);

    $result = $this->runner->run($flow);

    expect($result->status)->toBe('failed');
    expect($result->context->get('flaky_attempts'))->toBe(2);

    Sleep::assertSequence([Sleep::for(5)->seconds()]);
});

it('never retries an action that cannot be retried', function () {
    config(['autobuilder.execution.retry_attempts' => 3]);

    $flow = ($this->makeFlow)(NonRetryableFlakyAction::class, [
        'fail_times' => 1,
        'retry_attempts' => 3,
    ]);

    $result = $this->runner->run($flow);

    expect($result->status)->toBe('failed');
    expect($result->context->get('flaky_attempts'))->toBe(1);

    Sleep::assertNeverSlept();
});

it('does not re-run an action when a downstream node fails', function () {
    config([
        'autobuilder.execution.retry_attempts' => 2,
        'autobuilder.execution.retry_delay' => 1,
    ]);

    $flow = ($this->makeFlow)(
        FlakyAction::class,
        ['fail_times' => 0, 'counter' => 'upstream_attempts'],
        [
            [
                'id' => 'action-2',
                'type' => 'action',
                'brick' => FlakyAction::class,
                'config' => ['fail_times' => 10, 'counter' => 'downstream_attempts'],
            ],
        ],
        [
            ['source' => 'action-1', 'target' => 'action-2'],
        ],
    );

    $result = $this->runner->run($flow);

    expect($result->status)->toBe('failed');
    expect($result->context->get('upstream_attempts'))->toBe(1);
    expect($result->context->get('downstream_attempts'))->toBe(3);
});

it('reads retry defaults from the configuration on the action', function () {
    config([
        'autobuilder.execution.retry_attempts' => 4,
        'autobuilder.execution.retry_delay' => 12,
    ]);

    $action = new SetVariable;

    expect($action->canRetry())->toBeTrue();
    expect($action->maxRetries())->toBe(4);
    expect($action->retryDelay())->toBe(12);
});

it('clamps negative retry configuration values to zero', function () {
    config([
        'autobuilder.execution.retry_attempts' => -2,
        'autobuilder.execution.retry_delay' => -5,
    ]);

    $action = new SetVariable;

    expect($action->maxRetries())->toBe(0);
    expect($action->retryDelay())->toBe(0);
});
