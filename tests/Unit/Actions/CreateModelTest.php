<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\CreateModel;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Grazulex\AutoBuilder\Registry\BrickRegistry;
use Grazulex\AutoBuilder\Tests\Fixtures\TestPost;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->registry = app(BrickRegistry::class);
    $this->registry->discover();

    // Create test table
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
});

// =============================================================================
// Metadata Tests
// =============================================================================

describe('metadata', function () {
    it('has correct metadata', function () {
        $brick = $this->registry->resolve(CreateModel::class);

        expect($brick->name())->toBe('Create Model');
        expect($brick->category())->toBe('Database');
        expect($brick->icon())->toBe('plus-circle');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(CreateModel::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('model');
        expect($fieldNames)->toContain('attributes');
        expect($fieldNames)->toContain('store_as');
    });

    it('has correct description', function () {
        $brick = $this->registry->resolve(CreateModel::class);

        expect($brick->description())->toContain('Creates');
    });

    it('has 3 fields total', function () {
        $brick = $this->registry->resolve(CreateModel::class);
        $fields = $brick->fields();

        expect($fields)->toHaveCount(3);
    });
});

// =============================================================================
// Model Creation Tests
// =============================================================================

describe('model creation', function () {
    it('creates model with attributes', function () {
        $brick = $this->registry->resolve(CreateModel::class, [
            'model' => TestPost::class,
            'attributes' => [
                'title' => 'Test Post',
                'content' => 'Test content',
                'status' => 'published',
            ],
            'store_as' => 'created_post',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('created_post'))->toBeInstanceOf(TestPost::class);
        expect($result->get('created_post')->title)->toBe('Test Post');
        expect($result->get('created_post')->content)->toBe('Test content');
        expect($result->get('created_post')->status)->toBe('published');
    });

    it('stores model id in context', function () {
        $brick = $this->registry->resolve(CreateModel::class, [
            'model' => TestPost::class,
            'attributes' => [
                'title' => 'Test Post',
            ],
            'store_as' => 'post',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('post_id'))->toBe($result->get('post')->id);
    });

    it('creates model in database', function () {
        $brick = $this->registry->resolve(CreateModel::class, [
            'model' => TestPost::class,
            'attributes' => [
                'title' => 'Database Test',
                'content' => 'Persisted content',
            ],
            'store_as' => 'created_post',
        ]);

        $context = new FlowContext('flow-1');
        $brick->handle($context);

        expect(TestPost::where('title', 'Database Test')->exists())->toBeTrue();
    });

    it('uses default store_as value', function () {
        $brick = $this->registry->resolve(CreateModel::class, [
            'model' => TestPost::class,
            'attributes' => [
                'title' => 'Default Store As',
            ],
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('created_model'))->toBeInstanceOf(TestPost::class);
        expect($result->get('created_model_id'))->not->toBeNull();
    });
});

// =============================================================================
// Variable Resolution Tests
// =============================================================================

describe('variable resolution', function () {
    it('resolves variables in attributes', function () {
        $brick = $this->registry->resolve(CreateModel::class, [
            'model' => TestPost::class,
            'attributes' => [
                'title' => '{{ post_title }}',
                'content' => '{{ post_content }}',
            ],
            'store_as' => 'new_post',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('post_title', 'Resolved Title');
        $context->set('post_content', 'Resolved content from context');

        $result = $brick->handle($context);

        expect($result->get('new_post')->title)->toBe('Resolved Title');
        expect($result->get('new_post')->content)->toBe('Resolved content from context');
    });

    it('resolves nested variables', function () {
        $brick = $this->registry->resolve(CreateModel::class, [
            'model' => TestPost::class,
            'attributes' => [
                'title' => '{{ user.name }}\'s Post',
                'status' => '{{ settings.default_status }}',
            ],
            'store_as' => 'new_post',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('user', ['name' => 'John']);
        $context->set('settings', ['default_status' => 'draft']);

        $result = $brick->handle($context);

        expect($result->get('new_post')->title)->toBe('John\'s Post');
        expect($result->get('new_post')->status)->toBe('draft');
    });

    it('handles mixed static and variable attributes', function () {
        $brick = $this->registry->resolve(CreateModel::class, [
            'model' => TestPost::class,
            'attributes' => [
                'title' => '{{ title }}',
                'content' => 'Static content',
                'status' => 'published',
                'views' => '{{ initial_views }}',
            ],
            'store_as' => 'post',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('title', 'Dynamic Title');
        $context->set('initial_views', 100);

        $result = $brick->handle($context);

        expect($result->get('post')->title)->toBe('Dynamic Title');
        expect($result->get('post')->content)->toBe('Static content');
        expect($result->get('post')->status)->toBe('published');
        expect($result->get('post')->views)->toBe(100);
    });
});

// =============================================================================
// JSON Attributes Tests
// =============================================================================

describe('json attributes', function () {
    it('parses JSON string attributes', function () {
        $brick = $this->registry->resolve(CreateModel::class, [
            'model' => TestPost::class,
            'attributes' => json_encode([
                'title' => 'JSON Title',
                'content' => 'JSON content',
            ]),
            'store_as' => 'post',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('post')->title)->toBe('JSON Title');
        expect($result->get('post')->content)->toBe('JSON content');
    });
});

// =============================================================================
// Logging Tests
// =============================================================================

describe('logging', function () {
    it('logs model creation', function () {
        $brick = $this->registry->resolve(CreateModel::class, [
            'model' => TestPost::class,
            'attributes' => [
                'title' => 'Logged Post',
            ],
            'store_as' => 'post',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        $infoLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'info');
        expect($infoLogs)->not->toBeEmpty();

        $messages = array_map(fn ($log) => $log['message'], $infoLogs);
        $allMessages = implode(' ', $messages);

        expect($allMessages)->toContain('Created');
    });
});

// =============================================================================
// Rollback Tests
// =============================================================================

describe('rollback', function () {
    it('deletes created model on rollback', function () {
        $brick = $this->registry->resolve(CreateModel::class, [
            'model' => TestPost::class,
            'attributes' => [
                'title' => 'Rollback Test',
            ],
            'store_as' => 'post',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        $postId = $result->get('post_id');
        expect(TestPost::find($postId))->not->toBeNull();

        // Rollback
        $brick->rollback($result);

        expect(TestPost::find($postId))->toBeNull();
    });

    it('handles rollback when model already deleted', function () {
        $brick = $this->registry->resolve(CreateModel::class, [
            'model' => TestPost::class,
            'attributes' => [
                'title' => 'Already Deleted',
            ],
            'store_as' => 'post',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        // Delete manually first
        $result->get('post')->delete();

        // Rollback should not throw
        expect(fn () => $brick->rollback($result))->not->toThrow(Exception::class);
    });
});

// =============================================================================
// Error Handling Tests
// =============================================================================

describe('error handling', function () {
    it('throws TypeError for null model class', function () {
        $brick = $this->registry->resolve(CreateModel::class, [
            'model' => null,
            'attributes' => [
                'title' => 'Test',
            ],
        ]);

        $context = new FlowContext('flow-1');

        // class_exists() throws TypeError when passed null
        expect(fn () => $brick->handle($context))->toThrow(TypeError::class);
    });

    it('logs error for non-existent model class', function () {
        $brick = $this->registry->resolve(CreateModel::class, [
            'model' => 'NonExistentModel',
            'attributes' => [
                'title' => 'Test',
            ],
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        $errorLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'error');
        expect($errorLogs)->not->toBeEmpty();

        $firstError = array_values($errorLogs)[0]['message'];
        expect($firstError)->toContain('not found');
    });

    it('returns context without model when class not found', function () {
        $brick = $this->registry->resolve(CreateModel::class, [
            'model' => 'NonExistentModel',
            'attributes' => [
                'title' => 'Test',
            ],
            'store_as' => 'created_post',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->handle($context);

        expect($result->get('created_post'))->toBeNull();
    });
});

// =============================================================================
// Default Values Tests
// =============================================================================

describe('default values', function () {
    it('uses default store_as of created_model', function () {
        $brick = $this->registry->resolve(CreateModel::class);
        $fields = $brick->fields();

        $storeAsField = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'store_as');
        $defaultValue = array_values($storeAsField)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe('created_model');
    });

    it('uses null as default attributes in field definition', function () {
        $brick = $this->registry->resolve(CreateModel::class);
        $fields = $brick->fields();

        $attributesField = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'attributes');
        $defaultValue = array_values($attributesField)[0]->toArray()['default'] ?? null;

        // Field doesn't set a default, but handle() defaults to [] internally
        expect($defaultValue)->toBeNull();
    });
});
