<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Conditions\UserHasPermission;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Grazulex\AutoBuilder\Registry\BrickRegistry;
use Illuminate\Foundation\Auth\User as Authenticatable;

beforeEach(function () {
    $this->registry = app(BrickRegistry::class);
    $this->registry->discover();
});

// =============================================================================
// Metadata Tests
// =============================================================================

describe('metadata', function () {
    it('has correct metadata', function () {
        $brick = $this->registry->resolve(UserHasPermission::class);

        expect($brick->name())->toBe('User Has Permission');
        expect($brick->category())->toBe('Authorization');
        expect($brick->icon())->toBe('key');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(UserHasPermission::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('user_field');
        expect($fieldNames)->toContain('permission');
    });

    it('has correct description', function () {
        $brick = $this->registry->resolve(UserHasPermission::class);

        expect($brick->description())->toContain('permission');
    });

    it('has 2 fields total', function () {
        $brick = $this->registry->resolve(UserHasPermission::class);
        $fields = $brick->fields();

        expect($fields)->toHaveCount(2);
    });
});

// =============================================================================
// Evaluation Tests
// =============================================================================

describe('evaluation', function () {
    it('returns false when user not found in context', function () {
        $brick = $this->registry->resolve(UserHasPermission::class, [
            'user_field' => 'missing_user',
            'permission' => 'edit articles',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeFalse();
    });

    it('returns false when user field is null', function () {
        $brick = $this->registry->resolve(UserHasPermission::class, [
            'user_field' => 'user',
            'permission' => 'edit articles',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('user', null);

        $result = $brick->evaluate($context);

        expect($result)->toBeFalse();
    });

    it('returns true when user has hasPermissionTo method returning true', function () {
        $user = new class extends Authenticatable
        {
            public function hasPermissionTo(string $permission): bool
            {
                return $permission === 'edit articles';
            }
        };

        $brick = $this->registry->resolve(UserHasPermission::class, [
            'user_field' => 'user',
            'permission' => 'edit articles',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('user', $user);

        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();
    });

    it('returns false when user has hasPermissionTo method returning false', function () {
        $user = new class extends Authenticatable
        {
            public function hasPermissionTo(string $permission): bool
            {
                return false;
            }
        };

        $brick = $this->registry->resolve(UserHasPermission::class, [
            'user_field' => 'user',
            'permission' => 'edit articles',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('user', $user);

        $result = $brick->evaluate($context);

        expect($result)->toBeFalse();
    });

    it('falls back to can method when hasPermissionTo not available', function () {
        $user = new class extends Authenticatable
        {
            public function can($ability, $arguments = []): bool
            {
                return $ability === 'edit articles';
            }
        };

        $brick = $this->registry->resolve(UserHasPermission::class, [
            'user_field' => 'user',
            'permission' => 'edit articles',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('user', $user);

        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();
    });

    it('returns false when can method returns false', function () {
        $user = new class extends Authenticatable
        {
            public function can($ability, $arguments = []): bool
            {
                return false;
            }
        };

        $brick = $this->registry->resolve(UserHasPermission::class, [
            'user_field' => 'user',
            'permission' => 'delete articles',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('user', $user);

        $result = $brick->evaluate($context);

        expect($result)->toBeFalse();
    });
});

// =============================================================================
// Default Values Tests
// =============================================================================

describe('default values', function () {
    it('uses default user_field of user', function () {
        $brick = $this->registry->resolve(UserHasPermission::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'user_field');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe('user');
    });
});

// =============================================================================
// Field Configuration Tests
// =============================================================================

describe('field configuration', function () {
    it('permission field is required', function () {
        $brick = $this->registry->resolve(UserHasPermission::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'permission');
        $required = array_values($field)[0]->toArray()['required'] ?? false;

        expect($required)->toBeTrue();
    });

    it('permission field supports variables', function () {
        $brick = $this->registry->resolve(UserHasPermission::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'permission');
        $supportsVariables = array_values($field)[0]->toArray()['supportsVariables'] ?? false;

        expect($supportsVariables)->toBeTrue();
    });
});
