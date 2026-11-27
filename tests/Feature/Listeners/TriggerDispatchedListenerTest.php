<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\LogMessage;
use Grazulex\AutoBuilder\BuiltIn\Triggers\OnModelCreated;
use Grazulex\AutoBuilder\Events\TriggerDispatched;
use Grazulex\AutoBuilder\Jobs\RunFlowJob;
use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Registry\BrickRegistry;
use Grazulex\AutoBuilder\Tests\Fixtures\TestPost;
use Grazulex\AutoBuilder\Trigger\TriggerManager;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    app(BrickRegistry::class)->discover();

    if (! Schema::hasTable('test_posts')) {
        Schema::create('test_posts', function ($table) {
            $table->id();
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('status')->default('draft');
            $table->integer('views')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    TestPost::flushEventListeners();

    $this->makeFlow = fn (bool $sync): Flow => Flow::create([
        'name' => 'Welcome new posts',
        'nodes' => [
            [
                'id' => 'trigger-1',
                'type' => 'trigger',
                'brick' => OnModelCreated::class,
                'config' => ['model' => TestPost::class],
            ],
            [
                'id' => 'action-1',
                'type' => 'action',
                'brick' => LogMessage::class,
                'config' => [
                    'message' => 'Welcome, {{ model.title }}.',
                    'level' => 'info',
                    'log_to_laravel' => false,
                    'log_to_context' => true,
                ],
            ],
        ],
        'edges' => [
            ['source' => 'trigger-1', 'target' => 'action-1'],
        ],
        'active' => true,
        'sync' => $sync,
    ]);
});

it('runs a sync flow immediately when its model trigger fires', function () {
    Queue::fake();

    $flow = ($this->makeFlow)(true);
    app(TriggerManager::class)->registerFlow($flow);

    TestPost::create(['title' => 'Alex Morgan']);

    Queue::assertNothingPushed();

    $run = $flow->runs()->first();

    expect($flow->runs()->count())->toBe(1)
        ->and($run->status)->toBe('completed')
        ->and(json_encode($run->logs))->toContain('Welcome, Alex Morgan.');
});

it('runs a sync flow immediately when the trigger event is dispatched directly', function () {
    Queue::fake();

    $flow = ($this->makeFlow)(true);

    event(new TriggerDispatched((string) $flow->id, OnModelCreated::class, ['model' => ['title' => 'Alex Morgan']]));

    Queue::assertNothingPushed();
    expect($flow->runs()->count())->toBe(1);
});

it('runs every flow immediately when async execution is disabled globally', function () {
    config(['autobuilder.execution.async' => false]);
    Queue::fake();

    $flow = ($this->makeFlow)(false);

    event(new TriggerDispatched((string) $flow->id, OnModelCreated::class, ['model' => ['title' => 'Alex Morgan']]));

    Queue::assertNothingPushed();
    expect($flow->runs()->count())->toBe(1);
});

it('queues a non-sync flow instead of running it immediately', function () {
    config(['autobuilder.execution.queue' => 'flows']);
    Queue::fake();

    $flow = ($this->makeFlow)(false);
    app(TriggerManager::class)->registerFlow($flow);

    TestPost::create(['title' => 'Alex Morgan']);

    Queue::assertPushedOn('flows', RunFlowJob::class);
    Queue::assertPushed(RunFlowJob::class, 1);
    expect($flow->runs()->count())->toBe(0);
});
