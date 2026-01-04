<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\UpdateModel;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Grazulex\AutoBuilder\Registry\BrickRegistry;
use Grazulex\AutoBuilder\Tests\Fixtures\TestPost;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->registry = app(BrickRegistry::class);
    $this->registry->discover();

    // Create test table
    if (!Schema::hasTable('test_posts')) {
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
        $brick = $this->registry->resolve(UpdateModel::class);

        expect($brick->name())->toBe('Update Model');
        expect($brick->category())->toBe('Database');
        expect($brick->icon())->toBe('edit');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(UpdateModel::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('model_field');
        expect($fieldNames)->toContain('model_class');
        expect($fieldNames)->toContain('attributes');
        expect($fieldNames)->toContain('store_as');
    });

    it('has correct description', function () {
        $brick = $this->registry->resolve(UpdateModel::class);

        expect($brick->description())->toContain('Updates');
    });

    it('has 4 fields total', function () {
        $brick = $this->registry->resolve(UpdateModel::class);
        $fields = $brick->fields();

        expect($fields)->toHaveCount(4);
    });
});

// =============================================================================
// Model Update Tests (with Model Instance)
// =============================================================================

describe('update with model instance', function () {
    it('updates model from context field', function () {
        $post = TestPost::create([
            'title' => 'Original Title',
            'content' => 'Original content',
            'status' => 'draft',
        ]);

        $brick = $this->registry->resolve(UpdateModel::class, [
            'model_field' => 'post',
            'attributes' => [
                'title' => 'Updated Title',
                'status' => 'published',
            ],
            'store_as' => 'updated_post',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('post', $post);

        $result = $brick->handle($context);

        expect($result->get('updated_post')->title)->toBe('Updated Title');
        expect($result->get('updated_post')->status)->toBe('published');
        expect($result->get('updated_post')->content)->toBe('Original content');
    });

    it('persists changes to database', function () {
        $post = TestPost::create([
            'title' => 'Before Update',
            'status' => 'draft',
        ]);

        $brick = $this->registry->resolve(UpdateModel::class, [
            'model_field' => 'post',
            'attributes' => [
                'title' => 'After Update',
                'status' => 'published',
            ],
            'store_as' => 'updated_post',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('post', $post);

        $brick->handle($context);

        $freshPost = TestPost::find($post->id);
        expect($freshPost->title)->toBe('After Update');
        expect($freshPost->status)->toBe('published');
    });

    it('returns fresh model instance', function () {
        $post = TestPost::create([
            'title' => 'Original',
            'views' => 0,
        ]);

        $brick = $this->registry->resolve(UpdateModel::class, [
            'model_field' => 'post',
            'attributes' => [
                'views' => 100,
            ],
            'store_as' => 'updated_post',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('post', $post);

        $result = $brick->handle($context);

        // Fresh model should have updated values
        expect($result->get('updated_post')->views)->toBe(100);
    });
});

// =============================================================================
// Model Update Tests (with ID)
// =============================================================================

describe('update with model id', function () {
    it('finds model by numeric id', function () {
        $post = TestPost::create([
            'title' => 'Find By ID',
            'status' => 'draft',
        ]);

        $brick = $this->registry->resolve(UpdateModel::class, [
            'model_field' => 'post_id',
            'model_class' => TestPost::class,
            'attributes' => [
                'title' => 'Found and Updated',
            ],
            'store_as' => 'updated_post',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('post_id', $post->id);

        $result = $brick->handle($context);

        expect($result->get('updated_post')->title)->toBe('Found and Updated');
    });

    it('finds model from array with id key', function () {
        $post = TestPost::create([
            'title' => 'Array ID Test',
            'status' => 'draft',
        ]);

        $brick = $this->registry->resolve(UpdateModel::class, [
            'model_field' => 'post_data',
            'model_class' => TestPost::class,
            'attributes' => [
                'title' => 'Updated from Array',
            ],
            'store_as' => 'updated_post',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('post_data', ['id' => $post->id, 'extra' => 'data']);

        $result = $brick->handle($context);

        expect($result->get('updated_post')->title)->toBe('Updated from Array');
    });
});

// =============================================================================
// Variable Resolution Tests
// =============================================================================

describe('variable resolution', function () {
    it('resolves variables in attributes', function () {
        $post = TestPost::create([
            'title' => 'Original',
            'content' => 'Original content',
        ]);

        $brick = $this->registry->resolve(UpdateModel::class, [
            'model_field' => 'post',
            'attributes' => [
                'title' => '{{ new_title }}',
                'content' => '{{ new_content }}',
            ],
            'store_as' => 'updated_post',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('post', $post);
        $context->set('new_title', 'Resolved Title');
        $context->set('new_content', 'Resolved content');

        $result = $brick->handle($context);

        expect($result->get('updated_post')->title)->toBe('Resolved Title');
        expect($result->get('updated_post')->content)->toBe('Resolved content');
    });

    it('resolves nested variables', function () {
        $post = TestPost::create([
            'title' => 'Original',
            'status' => 'draft',
        ]);

        $brick = $this->registry->resolve(UpdateModel::class, [
            'model_field' => 'post',
            'attributes' => [
                'title' => '{{ update.title }}',
                'status' => '{{ update.status }}',
            ],
            'store_as' => 'updated_post',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('post', $post);
        $context->set('update', [
            'title' => 'Nested Title',
            'status' => 'published',
        ]);

        $result = $brick->handle($context);

        expect($result->get('updated_post')->title)->toBe('Nested Title');
        expect($result->get('updated_post')->status)->toBe('published');
    });
});

// =============================================================================
// JSON Attributes Tests
// =============================================================================

describe('json attributes', function () {
    it('parses JSON string attributes', function () {
        $post = TestPost::create([
            'title' => 'JSON Test',
            'status' => 'draft',
        ]);

        $brick = $this->registry->resolve(UpdateModel::class, [
            'model_field' => 'post',
            'attributes' => json_encode([
                'title' => 'From JSON',
                'status' => 'published',
            ]),
            'store_as' => 'updated_post',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('post', $post);

        $result = $brick->handle($context);

        expect($result->get('updated_post')->title)->toBe('From JSON');
        expect($result->get('updated_post')->status)->toBe('published');
    });
});

// =============================================================================
// Original Storage Tests
// =============================================================================

describe('original storage', function () {
    it('stores original attributes in context', function () {
        $post = TestPost::create([
            'title' => 'Original Title',
            'content' => 'Original content',
            'status' => 'draft',
            'views' => 50,
        ]);

        $brick = $this->registry->resolve(UpdateModel::class, [
            'model_field' => 'post',
            'attributes' => [
                'title' => 'New Title',
            ],
            'store_as' => 'updated_post',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('post', $post);

        $result = $brick->handle($context);

        $original = $result->get('updated_post_original');
        expect($original)->toBeArray();
        expect($original['title'])->toBe('Original Title');
        expect($original['content'])->toBe('Original content');
        expect($original['status'])->toBe('draft');
    });
});

// =============================================================================
// Rollback Tests
// =============================================================================

describe('rollback', function () {
    it('restores original attributes on rollback', function () {
        $post = TestPost::create([
            'title' => 'Original Title',
            'status' => 'draft',
            'views' => 10,
        ]);

        $brick = $this->registry->resolve(UpdateModel::class, [
            'model_field' => 'post',
            'attributes' => [
                'title' => 'Updated Title',
                'status' => 'published',
                'views' => 100,
            ],
            'store_as' => 'updated_post',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('post', $post);

        $result = $brick->handle($context);

        // Verify update worked
        expect($result->get('updated_post')->title)->toBe('Updated Title');

        // Rollback
        $brick->rollback($result);

        // Verify rollback
        $freshPost = TestPost::find($post->id);
        expect($freshPost->title)->toBe('Original Title');
        expect($freshPost->status)->toBe('draft');
        expect($freshPost->views)->toBe(10);
    });

    it('logs rollback action', function () {
        $post = TestPost::create([
            'title' => 'Original',
        ]);

        $brick = $this->registry->resolve(UpdateModel::class, [
            'model_field' => 'post',
            'attributes' => [
                'title' => 'Updated',
            ],
            'store_as' => 'updated_post',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('post', $post);

        $result = $brick->handle($context);
        $brick->rollback($result);

        $infoLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'info');
        $messages = array_map(fn ($log) => $log['message'], $infoLogs);
        $allMessages = implode(' ', $messages);

        expect($allMessages)->toContain('Rolled back');
    });
});

// =============================================================================
// Error Handling Tests
// =============================================================================

describe('error handling', function () {
    it('logs error when model not found', function () {
        $brick = $this->registry->resolve(UpdateModel::class, [
            'model_field' => 'missing_post',
            'attributes' => [
                'title' => 'New Title',
            ],
            'store_as' => 'updated_post',
        ]);

        $context = new FlowContext('flow-1');

        $result = $brick->handle($context);

        $errorLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'error');
        expect($errorLogs)->not->toBeEmpty();

        $firstError = array_values($errorLogs)[0]['message'];
        expect($firstError)->toContain('Could not resolve model');
    });

    it('returns context without update when model not found', function () {
        $brick = $this->registry->resolve(UpdateModel::class, [
            'model_field' => 'missing_post',
            'attributes' => [
                'title' => 'New Title',
            ],
            'store_as' => 'updated_post',
        ]);

        $context = new FlowContext('flow-1');

        $result = $brick->handle($context);

        expect($result->get('updated_post'))->toBeNull();
    });

    it('handles non-existent model id', function () {
        $brick = $this->registry->resolve(UpdateModel::class, [
            'model_field' => 'post_id',
            'model_class' => TestPost::class,
            'attributes' => [
                'title' => 'New Title',
            ],
            'store_as' => 'updated_post',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('post_id', 99999);

        $result = $brick->handle($context);

        expect($result->get('updated_post'))->toBeNull();
    });
});

// =============================================================================
// Logging Tests
// =============================================================================

describe('logging', function () {
    it('logs successful update with model id', function () {
        $post = TestPost::create([
            'title' => 'Log Test',
        ]);

        $brick = $this->registry->resolve(UpdateModel::class, [
            'model_field' => 'post',
            'attributes' => [
                'title' => 'Updated',
            ],
            'store_as' => 'updated_post',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('post', $post);

        $result = $brick->handle($context);

        $infoLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'info');
        $messages = array_map(fn ($log) => $log['message'], $infoLogs);
        $allMessages = implode(' ', $messages);

        expect($allMessages)->toContain('Updated model');
        expect($allMessages)->toContain((string) $post->id);
    });
});

// =============================================================================
// Default Values Tests
// =============================================================================

describe('default values', function () {
    it('uses default store_as of updated_model', function () {
        $brick = $this->registry->resolve(UpdateModel::class);
        $fields = $brick->fields();

        $storeAsField = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'store_as');
        $defaultValue = array_values($storeAsField)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe('updated_model');
    });
});
