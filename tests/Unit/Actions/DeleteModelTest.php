<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Actions\DeleteModel;
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
        $brick = $this->registry->resolve(DeleteModel::class);

        expect($brick->name())->toBe('Delete Model');
        expect($brick->category())->toBe('Database');
        expect($brick->icon())->toBe('trash-2');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(DeleteModel::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('model_field');
        expect($fieldNames)->toContain('model_class');
        expect($fieldNames)->toContain('force_delete');
    });

    it('has correct description', function () {
        $brick = $this->registry->resolve(DeleteModel::class);

        expect($brick->description())->toContain('Deletes');
    });

    it('has 3 fields total', function () {
        $brick = $this->registry->resolve(DeleteModel::class);
        $fields = $brick->fields();

        expect($fields)->toHaveCount(3);
    });
});

// =============================================================================
// Soft Delete Tests
// =============================================================================

describe('soft delete', function () {
    it('soft deletes model with SoftDeletes trait', function () {
        $post = TestPost::create([
            'title' => 'Soft Delete Test',
            'content' => 'Content to soft delete',
        ]);

        $brick = $this->registry->resolve(DeleteModel::class, [
            'model_field' => 'post',
            'force_delete' => false,
        ]);

        $context = new FlowContext('flow-1');
        $context->set('post', $post);

        $brick->handle($context);

        // Model should be soft deleted
        expect(TestPost::find($post->id))->toBeNull();
        expect(TestPost::withTrashed()->find($post->id))->not->toBeNull();
    });

    it('keeps model in withTrashed query', function () {
        $post = TestPost::create([
            'title' => 'Trashed Query Test',
        ]);

        $brick = $this->registry->resolve(DeleteModel::class, [
            'model_field' => 'post',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('post', $post);

        $brick->handle($context);

        $trashedPost = TestPost::withTrashed()->find($post->id);
        expect($trashedPost)->not->toBeNull();
        expect($trashedPost->deleted_at)->not->toBeNull();
    });
});

// =============================================================================
// Force Delete Tests
// =============================================================================

describe('force delete', function () {
    it('permanently deletes model when force_delete is true', function () {
        $post = TestPost::create([
            'title' => 'Force Delete Test',
        ]);

        $brick = $this->registry->resolve(DeleteModel::class, [
            'model_field' => 'post',
            'force_delete' => true,
        ]);

        $context = new FlowContext('flow-1');
        $context->set('post', $post);

        $brick->handle($context);

        // Model should be completely gone
        expect(TestPost::find($post->id))->toBeNull();
        expect(TestPost::withTrashed()->find($post->id))->toBeNull();
    });

    it('logs force delete action', function () {
        $post = TestPost::create([
            'title' => 'Force Delete Log Test',
        ]);

        $brick = $this->registry->resolve(DeleteModel::class, [
            'model_field' => 'post',
            'force_delete' => true,
        ]);

        $context = new FlowContext('flow-1');
        $context->set('post', $post);

        $result = $brick->handle($context);

        $infoLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'info');
        $messages = array_map(fn ($log) => $log['message'], $infoLogs);
        $allMessages = implode(' ', $messages);

        expect($allMessages)->toContain('Force deleted');
    });
});

// =============================================================================
// Model Resolution Tests (by ID)
// =============================================================================

describe('model resolution by id', function () {
    it('deletes model by numeric id', function () {
        $post = TestPost::create([
            'title' => 'Delete By ID',
        ]);

        $brick = $this->registry->resolve(DeleteModel::class, [
            'model_field' => 'post_id',
            'model_class' => TestPost::class,
        ]);

        $context = new FlowContext('flow-1');
        $context->set('post_id', $post->id);

        $brick->handle($context);

        expect(TestPost::find($post->id))->toBeNull();
    });

    it('deletes model from array with id key', function () {
        $post = TestPost::create([
            'title' => 'Delete From Array',
        ]);

        $brick = $this->registry->resolve(DeleteModel::class, [
            'model_field' => 'post_data',
            'model_class' => TestPost::class,
        ]);

        $context = new FlowContext('flow-1');
        $context->set('post_data', ['id' => $post->id, 'extra' => 'data']);

        $brick->handle($context);

        expect(TestPost::find($post->id))->toBeNull();
    });
});

// =============================================================================
// Deleted Data Storage Tests
// =============================================================================

describe('deleted data storage', function () {
    it('stores deleted model data in context', function () {
        $post = TestPost::create([
            'title' => 'Store Data Test',
            'content' => 'Content to remember',
            'status' => 'published',
        ]);

        $brick = $this->registry->resolve(DeleteModel::class, [
            'model_field' => 'post',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('post', $post);

        $result = $brick->handle($context);

        $deletedData = $result->get('_deleted_model_data');
        expect($deletedData)->toBeArray();
        expect($deletedData['class'])->toBe(TestPost::class);
        expect($deletedData['attributes']['title'])->toBe('Store Data Test');
        expect($deletedData['attributes']['content'])->toBe('Content to remember');
    });
});

// =============================================================================
// Rollback Tests
// =============================================================================

describe('rollback', function () {
    it('recreates model on rollback after soft delete', function () {
        $post = TestPost::create([
            'title' => 'Rollback Restore Test',
            'content' => 'Content to restore',
        ]);
        $postId = $post->id;

        $brick = $this->registry->resolve(DeleteModel::class, [
            'model_field' => 'post',
            'force_delete' => false,
        ]);

        $context = new FlowContext('flow-1');
        $context->set('post', $post);

        $result = $brick->handle($context);

        // Verify soft deleted
        expect(TestPost::find($postId))->toBeNull();
        expect(TestPost::withTrashed()->find($postId))->not->toBeNull();

        // Rollback
        $brick->rollback($result);

        // Verify restored (rollback tries restore first, then recreate)
        $restoredPost = TestPost::withTrashed()->find($postId);
        expect($restoredPost)->not->toBeNull();
        expect($restoredPost->title)->toBe('Rollback Restore Test');
    });

    it('logs rollback action', function () {
        $post = TestPost::create([
            'title' => 'Rollback Log Test',
        ]);

        $brick = $this->registry->resolve(DeleteModel::class, [
            'model_field' => 'post',
            'force_delete' => false,
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

    it('handles rollback when no deleted data exists', function () {
        $brick = $this->registry->resolve(DeleteModel::class, [
            'model_field' => 'post',
        ]);

        $context = new FlowContext('flow-1');

        // Rollback on empty context should not throw
        expect(fn () => $brick->rollback($context))->not->toThrow(Exception::class);
    });
});

// =============================================================================
// Error Handling Tests
// =============================================================================

describe('error handling', function () {
    it('logs warning when model not found', function () {
        $brick = $this->registry->resolve(DeleteModel::class, [
            'model_field' => 'missing_post',
        ]);

        $context = new FlowContext('flow-1');

        $result = $brick->handle($context);

        $warningLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'warning');
        expect($warningLogs)->not->toBeEmpty();

        $firstWarning = array_values($warningLogs)[0]['message'];
        expect($firstWarning)->toContain('Model not found');
    });

    it('returns context without action when model not found', function () {
        $brick = $this->registry->resolve(DeleteModel::class, [
            'model_field' => 'missing_post',
        ]);

        $context = new FlowContext('flow-1');

        $result = $brick->handle($context);

        expect($result->get('_deleted_model_data'))->toBeNull();
    });

    it('handles non-existent model id gracefully', function () {
        $brick = $this->registry->resolve(DeleteModel::class, [
            'model_field' => 'post_id',
            'model_class' => TestPost::class,
        ]);

        $context = new FlowContext('flow-1');
        $context->set('post_id', 99999);

        $result = $brick->handle($context);

        $warningLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'warning');
        expect($warningLogs)->not->toBeEmpty();
    });
});

// =============================================================================
// Logging Tests
// =============================================================================

describe('logging', function () {
    it('logs soft delete with model class', function () {
        $post = TestPost::create([
            'title' => 'Log Class Test',
        ]);

        $brick = $this->registry->resolve(DeleteModel::class, [
            'model_field' => 'post',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('post', $post);

        $result = $brick->handle($context);

        $infoLogs = array_filter($result->logs, fn ($log) => $log['level'] === 'info');
        $messages = array_map(fn ($log) => $log['message'], $infoLogs);
        $allMessages = implode(' ', $messages);

        expect($allMessages)->toContain('Deleted model');
        expect($allMessages)->toContain('TestPost');
    });
});

// =============================================================================
// Default Values Tests
// =============================================================================

describe('default values', function () {
    it('uses default force_delete of false', function () {
        $brick = $this->registry->resolve(DeleteModel::class);
        $fields = $brick->fields();

        $forceDeleteField = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'force_delete');
        $defaultValue = array_values($forceDeleteField)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBeFalse();
    });
});

// =============================================================================
// Multiple Deletes Tests
// =============================================================================

describe('multiple deletes', function () {
    it('deletes multiple models sequentially', function () {
        $post1 = TestPost::create(['title' => 'Post 1']);
        $post2 = TestPost::create(['title' => 'Post 2']);
        $post3 = TestPost::create(['title' => 'Post 3']);

        $brick = $this->registry->resolve(DeleteModel::class, [
            'model_field' => 'post',
        ]);

        foreach ([$post1, $post2, $post3] as $post) {
            $context = new FlowContext('flow-1');
            $context->set('post', $post);
            $brick->handle($context);
        }

        expect(TestPost::count())->toBe(0);
        expect(TestPost::withTrashed()->count())->toBe(3);
    });
});
