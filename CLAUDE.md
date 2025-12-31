# Laravel AutoBuilder - Project Context

## Project Overview

Laravel AutoBuilder is a visual automation builder for Laravel applications, inspired by Node-RED, n8n, and Zapier. It allows developers to create complex automations using a drag-and-drop interface.

**Package:** `grazulex/laravel-autobuilder`
**Namespace:** `Grazulex\AutoBuilder`
**Requirements:** PHP 8.2+, Laravel 10.x/11.x, Node.js 18+

---

## Core Concepts

### Bricks
The fundamental building blocks. Every node in a flow is a Brick:
- **Trigger** - Starts a flow (events, webhooks, schedules)
- **Condition** - Filters or routes (field checks, user roles)
- **Action** - Does something (notifications, CRUD, API calls)

### Flows
Complete automation workflows stored as JSON:
- Contains nodes (bricks) and edges (connections)
- Can be exported/imported between projects
- Supports test mode execution

### FlowContext
Data object traveling through flows:
- Initial payload (trigger data)
- Variables added by nodes
- Execution logs for debugging

### Fields
Configuration UI components for bricks:
- Text, Textarea, Select, Toggle, Number
- Code, KeyValue, ModelSelect
- Support for variable templating `{{ variable }}`

---

## Architecture & Directory Structure

```
src/
├── Bricks/
│   ├── Brick.php              # Abstract base class
│   ├── Trigger.php            # Abstract trigger class
│   ├── Condition.php          # Abstract condition class
│   └── Action.php             # Abstract action class
│
├── BuiltIn/
│   ├── Triggers/              # OnModelCreated, OnSchedule, OnWebhook...
│   ├── Conditions/            # FieldEquals, UserHasRole, TimeIsBetween...
│   └── Actions/               # SendNotification, CreateModel, CallWebhook...
│
├── Fields/                    # Field types for brick configuration
├── Flow/                      # FlowContext, FlowRunner, FlowValidator
├── Registry/                  # BrickRegistry, BrickDiscovery
├── Models/                    # Flow, FlowRun, FlowRunLog
├── Http/                      # Controllers, Middleware, Resources
├── Console/                   # Artisan commands
├── Events/                    # FlowStarted, FlowCompleted, BrickExecuted...
├── Exceptions/                # Custom exceptions
└── AutoBuilderServiceProvider.php
```

---

## Code Conventions

### PHP Standards
- PSR-12 coding style
- PHP 8.2+ features (readonly, enums, match expressions)
- Strict types declaration in all files
- Type hints for all parameters and return types

### Brick Implementation
Each brick must implement:
```php
public function name(): string;        // Display name
public function description(): string; // Help text
public function icon(): string;        // Lucide icon name
public function category(): string;    // Grouping in UI
public function fields(): array;       // Configuration fields
```

Triggers must implement:
```php
public function register(): void;      // Register event listeners
public function dispatch(array $payload): void;
```

Conditions must implement:
```php
public function evaluate(FlowContext $context): bool;
```

Actions must implement:
```php
public function handle(FlowContext $context): FlowContext;
public function rollback(FlowContext $context): void; // Optional
```

### Naming Conventions
- Bricks: PascalCase, descriptive (`OnModelCreated`, `SendNotification`)
- Fields: camelCase for make() parameter (`webhook_url`, `recipient_field`)
- Variables: snake_case in context (`created_model`, `api_response`)

### Field Definition Pattern
```php
Text::make('field_name')
    ->label('Display Label')
    ->description('Help text')
    ->placeholder('Example')
    ->default('value')
    ->required()
    ->supportsVariables()
    ->visibleWhen('other_field', 'value');
```

---

## Database Tables

- `autobuilder_flows` - Flow definitions (nodes, edges, config)
- `autobuilder_flow_runs` - Execution history
- `autobuilder_flow_run_logs` - Detailed execution logs

All tables use ULID primary keys.

---

## Testing Guidelines

### Flow Testing
```php
use Grazulex\AutoBuilder\Testing\FlowTestCase;

$result = $this->runFlow($flow, $payload);
$this->assertFlowCompleted($result);
```

### Brick Testing
```php
use Grazulex\AutoBuilder\Testing\BrickTestCase;

$context = $this->createContext(['user' => ['name' => 'Test']]);
$result = $brick->handle($context);
```

### Test Requirements
- Unit tests for all bricks
- Integration tests for flow execution
- Mock external services (Http::fake(), Notification::fake())

---

## Development Workflow

### Task Management (Backmark)
All development tasks must use Backmark:
```bash
backmark task create "Feature description" -a "@claude" -p high
backmark task ai-plan <id> "Implementation plan..."
backmark task ai-note <id> "Progress notes..."
backmark task ai-review <id> "Self-review..."
```

### Docker Environment (Stackmark)
```bash
stackmark init          # Initialize with Laravel template
stackmark add laravel-autobuilder --domain autobuilder.local
sudo stackmark hosts sync
stackmark start
```

### Git Commits
Follow Conventional Commits:
- `feat:` - New feature
- `fix:` - Bug fix
- `refactor:` - Code refactoring
- `test:` - Tests
- `docs:` - Documentation
- `chore:` - Maintenance

Example: `feat(bricks): add Discord notification action`

---

## Key Files Reference

| File | Purpose |
|------|---------|
| `src/Bricks/Brick.php` | Abstract base class for all bricks |
| `src/Flow/FlowContext.php` | Context object passing through flows |
| `src/Flow/FlowRunner.php` | Flow execution engine |
| `src/Registry/BrickRegistry.php` | Brick discovery and registration |
| `config/autobuilder.php` | Package configuration |

---

## Variable Templating

Flows support Blade-like syntax:
- `{{ user.name }}` - Access nested values
- `{{ amount | upper }}` - Apply filters
- Filters: `upper`, `lower`, `ucfirst`, `json`, `date`, `datetime`, `count`

---

## Security Considerations

- Webhook endpoints validate signatures via `X-Webhook-Secret` header
- Custom code execution (CustomClosure) can be disabled in config
- Forbidden functions: `eval`, `exec`, `system`, `shell_exec`, `passthru`
- Authorization gate: `access-autobuilder`

---

## UI Technology Stack

- React Flow - Node-based editor
- Tailwind CSS - Styling
- Headless UI - Accessible components
- Lucide Icons - Icon set
- Inertia.js/Livewire - Laravel integration

---

## Development Notes

- Always use `->supportsVariables()` for fields that should accept `{{ }}` syntax
- Use `FlowContext->log()` for debugging during development
- Bricks must be stateless - all config via `$this->config()`
- Test flows in dry-run mode before activating
