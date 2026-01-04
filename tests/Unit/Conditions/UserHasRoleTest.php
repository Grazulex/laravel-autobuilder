<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Conditions\UserHasRole;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Grazulex\AutoBuilder\Registry\BrickRegistry;
use Illuminate\Database\Eloquent\Model;
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
        $brick = $this->registry->resolve(UserHasRole::class);

        expect($brick->name())->toBe('User Has Role');
        expect($brick->category())->toBe('Authorization');
        expect($brick->icon())->toBe('shield-check');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(UserHasRole::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('user_field');
        expect($fieldNames)->toContain('role');
    });

    it('has correct description', function () {
        $brick = $this->registry->resolve(UserHasRole::class);

        expect($brick->description())->toContain('role');
    });

    it('has 2 fields total', function () {
        $brick = $this->registry->resolve(UserHasRole::class);
        $fields = $brick->fields();

        expect($fields)->toHaveCount(2);
    });
});

// =============================================================================
// Evaluation Tests
// =============================================================================

describe('evaluation', function () {
    it('returns false when user not found in context', function () {
        $brick = $this->registry->resolve(UserHasRole::class, [
            'user_field' => 'missing_user',
            'role' => 'admin',
        ]);

        $context = new FlowContext('flow-1');
        $result = $brick->evaluate($context);

        expect($result)->toBeFalse();
    });

    it('returns false when user field is null', function () {
        $brick = $this->registry->resolve(UserHasRole::class, [
            'user_field' => 'user',
            'role' => 'admin',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('user', null);

        $result = $brick->evaluate($context);

        expect($result)->toBeFalse();
    });

    it('returns true when user has role attribute matching', function () {
        $user = new class extends Authenticatable
        {
            public string $role = 'admin';
        };

        $brick = $this->registry->resolve(UserHasRole::class, [
            'user_field' => 'user',
            'role' => 'admin',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('user', $user);

        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();
    });

    it('returns false when user role attribute does not match', function () {
        $user = new class extends Authenticatable
        {
            public string $role = 'user';
        };

        $brick = $this->registry->resolve(UserHasRole::class, [
            'user_field' => 'user',
            'role' => 'admin',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('user', $user);

        $result = $brick->evaluate($context);

        expect($result)->toBeFalse();
    });

    it('returns true when user has hasRole method returning true', function () {
        $user = new class extends Authenticatable
        {
            public function hasRole(string $role): bool
            {
                return $role === 'admin';
            }
        };

        $brick = $this->registry->resolve(UserHasRole::class, [
            'user_field' => 'user',
            'role' => 'admin',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('user', $user);

        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();
    });

    it('returns false when user has hasRole method returning false', function () {
        $user = new class extends Authenticatable
        {
            public function hasRole(string $role): bool
            {
                return false;
            }
        };

        $brick = $this->registry->resolve(UserHasRole::class, [
            'user_field' => 'user',
            'role' => 'admin',
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
        $brick = $this->registry->resolve(UserHasRole::class);
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
    it('role field is required', function () {
        $brick = $this->registry->resolve(UserHasRole::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'role');
        $required = array_values($field)[0]->toArray()['required'] ?? false;

        expect($required)->toBeTrue();
    });

    it('role field supports variables', function () {
        $brick = $this->registry->resolve(UserHasRole::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'role');
        $supportsVariables = array_values($field)[0]->toArray()['supportsVariables'] ?? false;

        expect($supportsVariables)->toBeTrue();
    });
});
