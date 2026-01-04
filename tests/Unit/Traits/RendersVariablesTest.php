<?php

declare(strict_types=1);

use Carbon\Carbon;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Grazulex\AutoBuilder\Traits\RendersVariables;

// Test class that exposes the trait methods for testing
class RendersVariablesTestClass
{
    use RendersVariables;

    public function testRender(string $template, FlowContext $context): string
    {
        return $this->render($template, $context);
    }

    public function testApplyFilter(mixed $value, string $filter): mixed
    {
        return $this->applyFilter($value, $filter);
    }

    public function testResolveValue(mixed $value, FlowContext $context): mixed
    {
        return $this->resolveValue($value, $context);
    }

    public function testResolveKeyValue(array $data, FlowContext $context): array
    {
        return $this->resolveKeyValue($data, $context);
    }
}

beforeEach(function () {
    $this->renderer = new RendersVariablesTestClass();
});

// =============================================================================
// render() - Basic Rendering Tests
// =============================================================================

describe('render()', function () {
    it('renders a simple variable', function () {
        $context = new FlowContext('flow-1', ['name' => 'John']);

        $result = $this->renderer->testRender('Hello {{ name }}!', $context);

        expect($result)->toBe('Hello John!');
    });

    it('renders a nested variable', function () {
        $context = new FlowContext('flow-1', [
            'user' => ['name' => 'Jane', 'email' => 'jane@example.com'],
        ]);

        $result = $this->renderer->testRender('{{ user.name }} <{{ user.email }}>', $context);

        expect($result)->toBe('Jane <jane@example.com>');
    });

    it('renders multiple variables', function () {
        $context = new FlowContext('flow-1', [
            'first' => 'Hello',
            'second' => 'World',
        ]);

        $result = $this->renderer->testRender('{{ first }} {{ second }}!', $context);

        expect($result)->toBe('Hello World!');
    });

    it('returns template unchanged when no variables', function () {
        $context = new FlowContext('flow-1');

        $result = $this->renderer->testRender('No variables here', $context);

        expect($result)->toBe('No variables here');
    });

    it('returns empty string for missing variable', function () {
        $context = new FlowContext('flow-1');

        $result = $this->renderer->testRender('Hello {{ missing }}!', $context);

        expect($result)->toBe('Hello !');
    });

    it('handles whitespace around variable names', function () {
        $context = new FlowContext('flow-1', ['name' => 'Test']);

        $result = $this->renderer->testRender('{{  name  }}', $context);

        expect($result)->toBe('Test');
    });

    it('handles deeply nested variables', function () {
        $context = new FlowContext('flow-1', [
            'level1' => ['level2' => ['level3' => 'deep value']],
        ]);

        $result = $this->renderer->testRender('{{ level1.level2.level3 }}', $context);

        expect($result)->toBe('deep value');
    });
});

// =============================================================================
// applyFilter() - Filter Tests
// =============================================================================

describe('applyFilter()', function () {
    // String manipulation filters
    it('applies upper filter', function () {
        $result = $this->renderer->testApplyFilter('hello', 'upper');

        expect($result)->toBe('HELLO');
    });

    it('applies lower filter', function () {
        $result = $this->renderer->testApplyFilter('HELLO', 'lower');

        expect($result)->toBe('hello');
    });

    it('applies ucfirst filter', function () {
        $result = $this->renderer->testApplyFilter('hello world', 'ucfirst');

        expect($result)->toBe('Hello world');
    });

    it('applies ucwords filter', function () {
        $result = $this->renderer->testApplyFilter('hello world', 'ucwords');

        expect($result)->toBe('Hello World');
    });

    it('applies trim filter', function () {
        $result = $this->renderer->testApplyFilter('  hello  ', 'trim');

        expect($result)->toBe('hello');
    });

    // JSON filter
    it('applies json filter to array', function () {
        $result = $this->renderer->testApplyFilter(['a' => 1, 'b' => 2], 'json');

        expect($result)->toBe('{"a":1,"b":2}');
    });

    it('applies json filter to string', function () {
        $result = $this->renderer->testApplyFilter('hello', 'json');

        expect($result)->toBe('"hello"');
    });

    // Date filters
    it('applies date filter to Carbon instance', function () {
        $carbon = Carbon::create(2025, 6, 15, 14, 30, 0);
        $result = $this->renderer->testApplyFilter($carbon, 'date');

        expect($result)->toBe('2025-06-15');
    });

    it('applies datetime filter to Carbon instance', function () {
        $carbon = Carbon::create(2025, 6, 15, 14, 30, 45);
        $result = $this->renderer->testApplyFilter($carbon, 'datetime');

        expect($result)->toBe('2025-06-15 14:30:45');
    });

    it('applies time filter to Carbon instance', function () {
        $carbon = Carbon::create(2025, 6, 15, 14, 30, 45);
        $result = $this->renderer->testApplyFilter($carbon, 'time');

        expect($result)->toBe('14:30:45');
    });

    it('applies date filter to string date', function () {
        $result = $this->renderer->testApplyFilter('2025-06-15', 'date');

        expect($result)->toBe('2025-06-15');
    });

    it('returns empty string for invalid date', function () {
        $result = $this->renderer->testApplyFilter([], 'date');

        expect($result)->toBe('');
    });

    // Count filter
    it('applies count filter to array', function () {
        $result = $this->renderer->testApplyFilter([1, 2, 3, 4, 5], 'count');

        expect($result)->toBe(5);
    });

    it('applies count filter to string (returns length)', function () {
        $result = $this->renderer->testApplyFilter('hello', 'count');

        expect($result)->toBe(5);
    });

    // Array element filters
    it('applies first filter to array', function () {
        $result = $this->renderer->testApplyFilter(['a', 'b', 'c'], 'first');

        expect($result)->toBe('a');
    });

    it('applies first filter to empty array', function () {
        $result = $this->renderer->testApplyFilter([], 'first');

        expect($result)->toBe('');
    });

    it('applies last filter to array', function () {
        $result = $this->renderer->testApplyFilter(['a', 'b', 'c'], 'last');

        expect($result)->toBe('c');
    });

    it('applies join filter to array', function () {
        $result = $this->renderer->testApplyFilter(['a', 'b', 'c'], 'join');

        expect($result)->toBe('a, b, c');
    });

    it('applies keys filter to array', function () {
        $result = $this->renderer->testApplyFilter(['x' => 1, 'y' => 2], 'keys');

        expect($result)->toBe(['x', 'y']);
    });

    it('applies values filter to array', function () {
        $result = $this->renderer->testApplyFilter(['x' => 1, 'y' => 2], 'values');

        expect($result)->toBe([1, 2]);
    });

    it('applies reverse filter to array', function () {
        $result = $this->renderer->testApplyFilter([1, 2, 3], 'reverse');

        expect($result)->toBe([3, 2, 1]);
    });

    it('applies reverse filter to string', function () {
        $result = $this->renderer->testApplyFilter('hello', 'reverse');

        expect($result)->toBe('olleh');
    });

    it('applies sort filter to array', function () {
        $result = $this->renderer->testApplyFilter([3, 1, 2], 'sort');

        expect(array_values($result))->toBe([1, 2, 3]);
    });

    it('applies unique filter to array', function () {
        $result = $this->renderer->testApplyFilter([1, 2, 2, 3, 3, 3], 'unique');

        expect(array_values($result))->toBe([1, 2, 3]);
    });

    // Default filter
    it('applies default filter to falsy value', function () {
        $result = $this->renderer->testApplyFilter('', 'default');

        expect($result)->toBe('');
    });

    it('applies default filter to truthy value', function () {
        $result = $this->renderer->testApplyFilter('value', 'default');

        expect($result)->toBe('value');
    });

    // Unknown filter
    it('returns value unchanged for unknown filter', function () {
        $result = $this->renderer->testApplyFilter('test', 'unknownfilter');

        expect($result)->toBe('test');
    });
});

// =============================================================================
// render() with filters
// =============================================================================

describe('render() with filters', function () {
    it('renders variable with filter', function () {
        $context = new FlowContext('flow-1', ['name' => 'john']);

        $result = $this->renderer->testRender('Hello {{ name | upper }}!', $context);

        expect($result)->toBe('Hello JOHN!');
    });

    it('renders variable with date filter', function () {
        $context = new FlowContext('flow-1', ['created_at' => '2025-06-15']);

        $result = $this->renderer->testRender('Date: {{ created_at | date }}', $context);

        expect($result)->toBe('Date: 2025-06-15');
    });

    it('renders variable with count filter', function () {
        $context = new FlowContext('flow-1', ['items' => [1, 2, 3]]);

        $result = $this->renderer->testRender('Total: {{ items | count }}', $context);

        expect($result)->toBe('Total: 3');
    });
});

// =============================================================================
// resolveValue() Tests
// =============================================================================

describe('resolveValue()', function () {
    it('resolves simple variable reference', function () {
        $context = new FlowContext('flow-1', ['key' => 'value']);

        $result = $this->renderer->testResolveValue('{{ key }}', $context);

        expect($result)->toBe('value');
    });

    it('resolves template with text and variable', function () {
        $context = new FlowContext('flow-1', ['name' => 'John']);

        $result = $this->renderer->testResolveValue('Hello {{ name }}', $context);

        expect($result)->toBe('Hello John');
    });

    it('resolves array of values with variables', function () {
        $context = new FlowContext('flow-1', ['a' => 'first', 'b' => 'second']);

        $result = $this->renderer->testResolveValue(['{{ a }}', '{{ b }}'], $context);

        expect($result)->toBe(['first', 'second']);
    });

    it('returns non-string value unchanged', function () {
        $context = new FlowContext('flow-1');

        expect($this->renderer->testResolveValue(42, $context))->toBe(42);
        expect($this->renderer->testResolveValue(true, $context))->toBe(true);
        expect($this->renderer->testResolveValue(null, $context))->toBe(null);
    });

    it('resolves nested arrays with variables', function () {
        $context = new FlowContext('flow-1', ['name' => 'Test']);

        $result = $this->renderer->testResolveValue([
            'level1' => ['value' => '{{ name }}'],
        ], $context);

        expect($result['level1']['value'])->toBe('Test');
    });

    it('returns raw value for simple variable reference', function () {
        $context = new FlowContext('flow-1', ['data' => ['nested' => 'value']]);

        $result = $this->renderer->testResolveValue('{{ data }}', $context);

        expect($result)->toBe(['nested' => 'value']);
    });
});

// =============================================================================
// resolveKeyValue() Tests
// =============================================================================

describe('resolveKeyValue()', function () {
    it('resolves values with variables', function () {
        $context = new FlowContext('flow-1', ['name' => 'John', 'age' => 30]);

        $result = $this->renderer->testResolveKeyValue([
            'username' => '{{ name }}',
            'years' => '{{ age }}',
        ], $context);

        expect($result)->toBe([
            'username' => 'John',
            'years' => 30,
        ]);
    });

    it('resolves keys with variables', function () {
        $context = new FlowContext('flow-1', ['field' => 'email']);

        $result = $this->renderer->testResolveKeyValue([
            '{{ field }}' => 'test@example.com',
        ], $context);

        expect($result)->toBe(['email' => 'test@example.com']);
    });

    it('handles mixed static and dynamic keys/values', function () {
        $context = new FlowContext('flow-1', ['key' => 'dynamic_key', 'val' => 'dynamic_val']);

        $result = $this->renderer->testResolveKeyValue([
            'static' => 'static_value',
            '{{ key }}' => '{{ val }}',
        ], $context);

        expect($result)->toBe([
            'static' => 'static_value',
            'dynamic_key' => 'dynamic_val',
        ]);
    });

    it('keeps integer keys unchanged', function () {
        $context = new FlowContext('flow-1', ['name' => 'John']);

        $result = $this->renderer->testResolveKeyValue([
            0 => '{{ name }}',
            1 => 'static',
        ], $context);

        expect($result)->toBe([
            0 => 'John',
            1 => 'static',
        ]);
    });
});

// =============================================================================
// Advanced Scenarios
// =============================================================================

describe('advanced scenarios', function () {
    it('applies filter on nested variable', function () {
        $context = new FlowContext('flow-1', [
            'user' => ['name' => 'john doe'],
        ]);

        $result = $this->renderer->testRender('{{ user.name | upper }}', $context);

        expect($result)->toBe('JOHN DOE');
    });

    it('handles realistic template with multiple features', function () {
        $context = new FlowContext('flow-1', [
            'user' => ['name' => 'john doe', 'email' => 'JOHN@EXAMPLE.COM'],
            'items' => ['apple', 'banana', 'cherry'],
        ]);

        $template = 'User: {{ user.name | ucwords }}, Email: {{ user.email | lower }}, Items ({{ items | count }}): {{ items | join }}';

        $result = $this->renderer->testRender($template, $context);

        expect($result)->toBe('User: John Doe, Email: john@example.com, Items (3): apple, banana, cherry');
    });

    it('renders variables from both payload and set variables', function () {
        $context = new FlowContext('flow-1', ['initial' => 'data']);
        $context->set('dynamic', 'value');

        $result = $this->renderer->testRender('{{ initial }} {{ dynamic }}', $context);

        expect($result)->toBe('data value');
    });

    it('handles missing nested path gracefully', function () {
        $context = new FlowContext('flow-1', ['user' => ['name' => 'Test']]);

        $result = $this->renderer->testRender('{{ user.email.address }}', $context);

        expect($result)->toBe('');
    });

    it('handles unicode characters in variables', function () {
        $context = new FlowContext('flow-1', ['name' => 'José García']);

        $result = $this->renderer->testRender('Hello {{ name }}!', $context);

        expect($result)->toBe('Hello José García!');
    });

    it('handles emoji in context values', function () {
        $context = new FlowContext('flow-1', ['emoji' => '🚀']);

        $result = $this->renderer->testRender('Rocket: {{ emoji }}', $context);

        expect($result)->toBe('Rocket: 🚀');
    });

    it('handles newlines in template', function () {
        $context = new FlowContext('flow-1', ['name' => 'Test']);

        $result = $this->renderer->testRender("Hello\n{{ name }}", $context);

        expect($result)->toBe("Hello\nTest");
    });

    it('handles malformed variable syntax - unclosed brackets', function () {
        $context = new FlowContext('flow-1', ['name' => 'Test']);

        $result = $this->renderer->testRender('Hello {{ name', $context);

        expect($result)->toBe('Hello {{ name');
    });
});

// =============================================================================
// Edge Cases
// =============================================================================

describe('edge cases', function () {
    it('handles null value in context', function () {
        $context = new FlowContext('flow-1', ['nullval' => null]);

        $result = $this->renderer->testRender('Value: {{ nullval }}', $context);

        expect($result)->toBe('Value: ');
    });

    it('handles empty string in context', function () {
        $context = new FlowContext('flow-1', ['empty' => '']);

        $result = $this->renderer->testRender('Value: {{ empty }}', $context);

        expect($result)->toBe('Value: ');
    });

    it('handles numeric value in context', function () {
        $context = new FlowContext('flow-1', ['number' => 42]);

        $result = $this->renderer->testRender('Number: {{ number }}', $context);

        expect($result)->toBe('Number: 42');
    });

    it('handles boolean value in context', function () {
        $context = new FlowContext('flow-1', ['flag' => true]);

        $result = $this->renderer->testRender('Flag: {{ flag }}', $context);

        expect($result)->toBe('Flag: 1');
    });

    it('handles special characters in template', function () {
        $context = new FlowContext('flow-1', ['name' => 'O\'Brien']);

        $result = $this->renderer->testRender('Name: {{ name }}', $context);

        expect($result)->toBe("Name: O'Brien");
    });

    it('handles curly braces in value', function () {
        $context = new FlowContext('flow-1', ['code' => 'function() { return 1; }']);

        $result = $this->renderer->testRender('Code: {{ code }}', $context);

        expect($result)->toBe('Code: function() { return 1; }');
    });

    it('handles consecutive variables without space', function () {
        $context = new FlowContext('flow-1', ['a' => 'Hello', 'b' => 'World']);

        $result = $this->renderer->testRender('{{ a }}{{ b }}', $context);

        expect($result)->toBe('HelloWorld');
    });
});
