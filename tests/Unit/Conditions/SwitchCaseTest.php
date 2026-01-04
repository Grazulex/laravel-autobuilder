<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Conditions\SwitchCase;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Grazulex\AutoBuilder\Registry\BrickRegistry;

beforeEach(function () {
    $this->registry = app(BrickRegistry::class);
    $this->registry->discover();
});

// =============================================================================
// Metadata Tests
// =============================================================================

describe('metadata', function () {
    it('has correct metadata', function () {
        $brick = $this->registry->resolve(SwitchCase::class);

        expect($brick->name())->toBe('Switch Case');
        expect($brick->category())->toBe('Logic');
        expect($brick->icon())->toBe('git-branch');
    });

    it('has required fields', function () {
        $brick = $this->registry->resolve(SwitchCase::class);
        $fields = $brick->fields();

        $fieldNames = array_map(fn ($f) => $f->toArray()['name'], $fields);

        expect($fieldNames)->toContain('value');
        expect($fieldNames)->toContain('cases');
        expect($fieldNames)->toContain('default_case');
        expect($fieldNames)->toContain('comparison');
        expect($fieldNames)->toContain('store_as');
    });

    it('has correct description', function () {
        $brick = $this->registry->resolve(SwitchCase::class);

        expect($brick->description())->toContain('case');
    });

    it('has 5 fields total', function () {
        $brick = $this->registry->resolve(SwitchCase::class);
        $fields = $brick->fields();

        expect($fields)->toHaveCount(5);
    });
});

// =============================================================================
// Evaluation Tests
// =============================================================================

describe('evaluation', function () {
    it('returns true and stores matched case when value matches', function () {
        $brick = $this->registry->resolve(SwitchCase::class, [
            'value' => '{{ role }}',
            'cases' => [
                'admin_case' => 'admin',
                'user_case' => 'user',
                'guest_case' => 'guest',
            ],
        ]);

        $context = new FlowContext('flow-1');
        $context->set('role', 'admin');

        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();
        expect($context->get('switch_matched_case'))->toBe('admin_case');
    });

    it('returns false when no case matches and no default', function () {
        $brick = $this->registry->resolve(SwitchCase::class, [
            'value' => '{{ role }}',
            'cases' => [
                'admin_case' => 'admin',
                'user_case' => 'user',
            ],
        ]);

        $context = new FlowContext('flow-1');
        $context->set('role', 'moderator');

        $result = $brick->evaluate($context);

        expect($result)->toBeFalse();
        expect($context->get('switch_matched_case'))->toBeNull();
    });

    it('uses default case when no match found', function () {
        $brick = $this->registry->resolve(SwitchCase::class, [
            'value' => '{{ role }}',
            'cases' => [
                'admin_case' => 'admin',
            ],
            'default_case' => 'other_case',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('role', 'moderator');

        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();
        expect($context->get('switch_matched_case'))->toBe('other_case');
    });

    it('stores switch value and cases in context', function () {
        $brick = $this->registry->resolve(SwitchCase::class, [
            'value' => '{{ status }}',
            'cases' => [
                'active_case' => 'active',
                'pending_case' => 'pending',
            ],
        ]);

        $context = new FlowContext('flow-1');
        $context->set('status', 'active');

        $brick->evaluate($context);

        expect($context->get('switch_value'))->toBe('active');
        expect($context->get('switch_cases'))->toBe(['active_case', 'pending_case']);
    });

    it('uses custom store_as variable name', function () {
        $brick = $this->registry->resolve(SwitchCase::class, [
            'value' => '{{ type }}',
            'cases' => [
                'email' => 'email',
            ],
            'store_as' => 'notification_type',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('type', 'email');

        $brick->evaluate($context);

        expect($context->get('notification_type'))->toBe('email');
    });

    it('supports strict comparison', function () {
        $brick = $this->registry->resolve(SwitchCase::class, [
            'value' => '{{ count }}',
            'cases' => [
                'ten' => 10,
            ],
            'comparison' => 'strict',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('count', '10'); // String, not integer

        $result = $brick->evaluate($context);

        expect($result)->toBeFalse();
    });

    it('supports loose comparison by default', function () {
        $brick = $this->registry->resolve(SwitchCase::class, [
            'value' => '{{ count }}',
            'cases' => [
                'ten' => 10,
            ],
            'comparison' => 'loose',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('count', '10'); // String, but loose compare should work

        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();
    });

    it('supports contains comparison', function () {
        $brick = $this->registry->resolve(SwitchCase::class, [
            'value' => '{{ email }}',
            'cases' => [
                'gmail' => 'gmail',
                'outlook' => 'outlook',
            ],
            'comparison' => 'contains',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('email', 'user@gmail.com');

        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();
        expect($context->get('switch_matched_case'))->toBe('gmail');
    });

    it('supports starts_with comparison', function () {
        $brick = $this->registry->resolve(SwitchCase::class, [
            'value' => '{{ code }}',
            'cases' => [
                'us_code' => 'US-',
                'eu_code' => 'EU-',
            ],
            'comparison' => 'starts_with',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('code', 'EU-12345');

        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();
        expect($context->get('switch_matched_case'))->toBe('eu_code');
    });

    it('supports ends_with comparison', function () {
        $brick = $this->registry->resolve(SwitchCase::class, [
            'value' => '{{ filename }}',
            'cases' => [
                'pdf' => '.pdf',
                'doc' => '.doc',
            ],
            'comparison' => 'ends_with',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('filename', 'report.pdf');

        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();
        expect($context->get('switch_matched_case'))->toBe('pdf');
    });

    it('supports regex comparison', function () {
        $brick = $this->registry->resolve(SwitchCase::class, [
            'value' => '{{ phone }}',
            'cases' => [
                'us_phone' => '/^\+1/',
                'uk_phone' => '/^\+44/',
            ],
            'comparison' => 'regex',
        ]);

        $context = new FlowContext('flow-1');
        $context->set('phone', '+44123456789');

        $result = $brick->evaluate($context);

        expect($result)->toBeTrue();
        expect($context->get('switch_matched_case'))->toBe('uk_phone');
    });

    it('logs matched case', function () {
        $brick = $this->registry->resolve(SwitchCase::class, [
            'value' => '{{ status }}',
            'cases' => [
                'active' => 'active',
            ],
        ]);

        $context = new FlowContext('flow-1');
        $context->set('status', 'active');

        $brick->evaluate($context);

        $infoLogs = array_filter($context->logs, fn ($log) => $log['level'] === 'info');
        expect($infoLogs)->not->toBeEmpty();

        $firstLog = array_values($infoLogs)[0]['message'];
        expect($firstLog)->toContain('matched case');
    });

    it('logs when no case matched', function () {
        $brick = $this->registry->resolve(SwitchCase::class, [
            'value' => '{{ status }}',
            'cases' => [
                'active' => 'active',
            ],
        ]);

        $context = new FlowContext('flow-1');
        $context->set('status', 'unknown');

        $brick->evaluate($context);

        $infoLogs = array_filter($context->logs, fn ($log) => $log['level'] === 'info');
        expect($infoLogs)->not->toBeEmpty();

        $firstLog = array_values($infoLogs)[0]['message'];
        expect($firstLog)->toContain('no case');
    });
});

// =============================================================================
// Default Values Tests
// =============================================================================

describe('default values', function () {
    it('uses default comparison of loose', function () {
        $brick = $this->registry->resolve(SwitchCase::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'comparison');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe('loose');
    });

    it('uses default store_as of switch_matched_case', function () {
        $brick = $this->registry->resolve(SwitchCase::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'store_as');
        $defaultValue = array_values($field)[0]->toArray()['default'] ?? null;

        expect($defaultValue)->toBe('switch_matched_case');
    });
});

// =============================================================================
// Field Configuration Tests
// =============================================================================

describe('field configuration', function () {
    it('value field is required', function () {
        $brick = $this->registry->resolve(SwitchCase::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'value');
        $required = array_values($field)[0]->toArray()['required'] ?? false;

        expect($required)->toBeTrue();
    });

    it('value field supports variables', function () {
        $brick = $this->registry->resolve(SwitchCase::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'value');
        $supportsVariables = array_values($field)[0]->toArray()['supportsVariables'] ?? false;

        expect($supportsVariables)->toBeTrue();
    });

    it('cases field is required', function () {
        $brick = $this->registry->resolve(SwitchCase::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'cases');
        $required = array_values($field)[0]->toArray()['required'] ?? false;

        expect($required)->toBeTrue();
    });

    it('cases field supports variables', function () {
        $brick = $this->registry->resolve(SwitchCase::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'cases');
        $supportsVariables = array_values($field)[0]->toArray()['supportsVariables'] ?? false;

        expect($supportsVariables)->toBeTrue();
    });

    it('comparison field has correct options', function () {
        $brick = $this->registry->resolve(SwitchCase::class);
        $fields = $brick->fields();

        $field = array_filter($fields, fn ($f) => $f->toArray()['name'] === 'comparison');
        $options = array_values($field)[0]->toArray()['options'] ?? [];

        expect(array_keys($options))->toContain('loose');
        expect(array_keys($options))->toContain('strict');
        expect(array_keys($options))->toContain('contains');
        expect(array_keys($options))->toContain('starts_with');
        expect(array_keys($options))->toContain('ends_with');
        expect(array_keys($options))->toContain('regex');
    });
});
