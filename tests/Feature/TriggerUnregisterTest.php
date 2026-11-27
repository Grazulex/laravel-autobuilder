<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\LogMessage;
use Grazulex\AutoBuilder\BuiltIn\Triggers\OnEventDispatched;
use Grazulex\AutoBuilder\BuiltIn\Triggers\OnModelCreated;
use Grazulex\AutoBuilder\Events\TriggerDispatched;
use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Registry\BrickRegistry;
use Grazulex\AutoBuilder\Tests\Fixtures\TestPost;
use Grazulex\AutoBuilder\Trigger\TriggerManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

final class TriggerUnregisterTestEvent
{
    public function __construct(public string $name = 'ping') {}
}

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
    Queue::fake();
    Event::fake([TriggerDispatched::class]);

    $this->manager = app(TriggerManager::class);
});

function unregisterTestFlow(array $triggerNode, array $attributes = []): Flow
{
    return Flow::create(array_merge([
        'name' => 'Unregister test flow',
        'nodes' => [
            array_merge(['id' => 'trigger-1', 'type' => 'trigger'], $triggerNode),
            [
                'id' => 'action-1',
                'type' => 'action',
                'brick' => LogMessage::class,
                'config' => ['message' => 'first', 'level' => 'info', 'log_to_laravel' => false],
            ],
        ],
        'edges' => [
            ['source' => 'trigger-1', 'target' => 'action-1'],
        ],
        'active' => true,
    ], $attributes));
}

function modelCreatedFlow(string $model = TestPost::class, array $attributes = []): Flow
{
    return unregisterTestFlow([
        'brick' => OnModelCreated::class,
        'config' => ['model' => $model],
    ], $attributes);
}

function withActionMessage(Flow $flow, string $message): array
{
    $nodes = $flow->nodes;
    $nodes[1]['config']['message'] = $message;

    return $nodes;
}

it('dispatches once after the flow was refreshed several times', function () {
    $flow = modelCreatedFlow();

    $this->manager->registerFlow($flow);
    $this->manager->refreshFlow($flow);
    $this->manager->refreshFlow($flow);

    TestPost::create(['title' => 'Hello']);

    Event::assertDispatchedTimes(TriggerDispatched::class, 1);
});

it('does not dispatch anymore once the flow is unregistered', function () {
    $flow = modelCreatedFlow();

    $this->manager->registerFlow($flow);
    $this->manager->refreshFlow($flow);
    $this->manager->unregisterFlow($flow);

    TestPost::create(['title' => 'Hello']);

    Event::assertNotDispatched(TriggerDispatched::class);
    expect($this->manager->getRegisteredTriggers())->toBe([]);
});

it('dispatches once when the same flow is registered twice', function () {
    $flow = modelCreatedFlow();

    $this->manager->registerFlow($flow);
    $this->manager->registerFlow($flow);

    TestPost::create(['title' => 'Hello']);

    Event::assertDispatchedTimes(TriggerDispatched::class, 1);
});

it('runs an active flow once after its nodes were saved twice', function () {
    $flow = modelCreatedFlow();
    $this->manager->registerFlow($flow);

    $flow->update(['nodes' => withActionMessage($flow, 'second')]);
    $flow->update(['nodes' => withActionMessage($flow, 'third')]);

    TestPost::create(['title' => 'Hello']);

    Event::assertDispatchedTimes(TriggerDispatched::class, 1);
});

it('stops dispatching when the flow is deactivated', function () {
    $flow = modelCreatedFlow();
    $this->manager->registerFlow($flow);

    $flow->update(['active' => false]);

    TestPost::create(['title' => 'Hello']);

    Event::assertNotDispatched(TriggerDispatched::class);
});

it('stops dispatching when the flow is deleted', function () {
    $flow = modelCreatedFlow();
    $this->manager->registerFlow($flow);

    $flow->delete();

    TestPost::create(['title' => 'Hello']);

    Event::assertNotDispatched(TriggerDispatched::class);
});

it('stops watching the previous model when the trigger model changes', function () {
    $flow = modelCreatedFlow();
    $this->manager->registerFlow($flow);

    $nodes = $flow->nodes;
    $nodes[0]['config']['model'] = Flow::class;
    $flow->update(['nodes' => $nodes]);

    TestPost::create(['title' => 'Hello']);

    Event::assertNotDispatched(TriggerDispatched::class);
});

it('stops dispatching event triggers once unregistered', function () {
    $flow = unregisterTestFlow([
        'brick' => OnEventDispatched::class,
        'config' => ['event' => TriggerUnregisterTestEvent::class],
    ]);

    $this->manager->registerFlow($flow);
    $this->manager->refreshFlow($flow);

    event(new TriggerUnregisterTestEvent);

    Event::assertDispatchedTimes(TriggerDispatched::class, 1);

    $this->manager->unregisterFlow($flow);

    event(new TriggerUnregisterTestEvent);

    Event::assertDispatchedTimes(TriggerDispatched::class, 1);
});

it('switches the trigger off from the base unregister method', function () {
    $trigger = app(BrickRegistry::class)->resolve(OnModelCreated::class, ['model' => TestPost::class]);
    $trigger->setFlowId('flow-1');
    $trigger->register();

    expect($trigger->isUnregistered())->toBeFalse();

    $trigger->unregister();

    TestPost::create(['title' => 'Hello']);

    expect($trigger->isUnregistered())->toBeTrue()
        ->and($trigger->getFlowId())->toBe('flow-1');
    Event::assertNotDispatched(TriggerDispatched::class);
});

it('switches off triggers whose unregister override skips the parent', function () {
    $flow = modelCreatedFlow();

    $nodes = $flow->nodes;
    $nodes[0]['brick'] = TriggerWithoutParentUnregister::class;
    $flow->nodes = $nodes;

    $this->manager->registerFlow($flow);
    $this->manager->unregisterFlow($flow);

    TestPost::create(['title' => 'Hello']);

    Event::assertNotDispatched(TriggerDispatched::class);
});

class TriggerWithoutParentUnregister extends OnModelCreated
{
    public function unregister(): void
    {
        // Releases nothing and does not call parent::unregister()
    }
}
