# Laravel AutoBuilder

> Visual automation builder for Laravel - Create workflows with a drag-and-drop interface

[![Latest Version on Packagist](https://img.shields.io/packagist/v/grazulex/laravel-autobuilder.svg?style=flat-square)](https://packagist.org/packages/grazulex/laravel-autobuilder)
[![Total Downloads](https://img.shields.io/packagist/dt/grazulex/laravel-autobuilder.svg?style=flat-square)](https://packagist.org/packages/grazulex/laravel-autobuilder)
[![License](https://img.shields.io/packagist/l/grazulex/laravel-autobuilder.svg?style=flat-square)](https://packagist.org/packages/grazulex/laravel-autobuilder)

---

## What is Laravel AutoBuilder?

Laravel AutoBuilder is a visual automation builder inspired by Node-RED, n8n, and Zapier. It allows you to create complex automations using a **drag-and-drop interface** directly in your Laravel application.

Build workflows that react to events, make decisions, and execute actions - all without writing code.

### Key Features

- **Visual Flow Editor** - Intuitive drag-and-drop interface built with Vue Flow
- **Trigger-based Automation** - React to model events, webhooks, schedules, and more
- **Conditional Logic** - Branch flows based on field values, user roles, or custom conditions
- **Rich Action Library** - Send notifications, create models, call APIs, transform data
- **Logic Gates** - AND, OR, AtLeast gates for complex decision making
- **Flow Validation** - Check for configuration errors before activation
- **Execution History** - Debug with detailed logs and variable inspection
- **Import/Export** - Share flows between projects as JSON files
- **Sync/Async Execution** - Choose immediate or queued execution per flow

---

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | 8.2+ |
| Laravel | 10.x, 11.x |
| Node.js | 18+ (for development) |

---

## Installation

```bash
composer require grazulex/laravel-autobuilder
```

Publish assets and run migrations:

```bash
php artisan vendor:publish --tag=autobuilder-assets
php artisan vendor:publish --tag=autobuilder-migrations
php artisan migrate
```

Access the visual editor at `/autobuilder`.

---

## Quick Example

Create a flow that sends a notification when a user is created:

1. Add a **"On Model Created"** trigger for the `User` model
2. Connect it to a **"Send Notification"** action
3. Configure the notification with `{{ user.email }}` and `{{ user.name }}`
4. Activate the flow

That's it! The automation will run whenever a new user is created.

---

## Documentation

For complete documentation, visit the [Wiki](https://github.com/Grazulex/laravel-autobuilder/wiki):

- [Installation](https://github.com/Grazulex/laravel-autobuilder/wiki/Installation)
- [Getting Started](https://github.com/Grazulex/laravel-autobuilder/wiki/Getting-Started)
- [Configuration](https://github.com/Grazulex/laravel-autobuilder/wiki/Configuration)
- [Creating Bricks](https://github.com/Grazulex/laravel-autobuilder/wiki/Creating-Bricks)
- [Built-in Bricks](https://github.com/Grazulex/laravel-autobuilder/wiki/Built-in-Bricks)

---

## Built-in Bricks

### Triggers
- **OnModelCreated** - Fire when an Eloquent model is created
- **OnModelUpdated** - Fire when a model is updated
- **OnModelDeleted** - Fire when a model is deleted
- **OnWebhook** - Receive external webhook calls
- **OnSchedule** - Run on a cron schedule
- **OnLogin** / **OnLogout** - React to authentication events

### Conditions
- **FieldEquals** - Check if a field matches a value
- **FieldContains** - Check if a field contains a value
- **UserHasRole** - Check user roles (Spatie compatible)
- **TimeIsBetween** - Check if current time is in a range
- **SwitchCase** - Multi-case comparison
- **Throttle** - Rate limiting condition
- **RandomChance** - A/B testing with configurable percentage

### Actions
- **SendNotification** - Send Laravel notifications
- **CreateModel** - Create Eloquent models
- **UpdateModel** - Update existing models
- **DeleteModel** - Delete models
- **CallWebhook** - Make HTTP requests to external APIs
- **SetVariable** - Store values for use in the flow
- **LogMessage** - Write to Laravel logs
- **TransformData** - Collection transformations (pluck, filter, sort, etc.)
- **CacheAction** - Cache operations (get, put, forget)
- **SubFlow** - Execute another flow as a subroutine
- **Delay** - Pause execution
- **LoopEach** - Iterate over collections

### Gates
- **AndGate** - All inputs must be true
- **OrGate** - At least one input must be true
- **AtLeastGate** - Flexible: min count, percentage, majority, or all/any

---

## Variable Templating

Use Blade-like syntax to reference data in your flows:

```
{{ user.name }}          - Access nested values
{{ amount | upper }}     - Apply filters
{{ items | count }}      - Get collection count
```

**Available filters:** `upper`, `lower`, `ucfirst`, `json`, `date`, `datetime`, `count`

---

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=autobuilder-config
```

Key options in `config/autobuilder.php`:

```php
return [
    'routes' => [
        'prefix' => 'autobuilder',
        'middleware' => ['web', 'auth'],
    ],
    'bricks' => [
        'paths' => [app_path('AutoBuilder/Bricks')],
        'namespace' => 'App\\AutoBuilder\\Bricks',
    ],
];
```

---

## Creating Custom Bricks

Create your own triggers, conditions, and actions:

```php
namespace App\AutoBuilder\Bricks;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Flow\FlowContext;

class SendSlackMessage extends Action
{
    public function name(): string
    {
        return 'Send Slack Message';
    }

    public function fields(): array
    {
        return [
            Text::make('channel')->label('Channel')->required(),
            Textarea::make('message')->label('Message')->supportsVariables(),
        ];
    }

    public function handle(FlowContext $context): FlowContext
    {
        // Your logic here
        return $context;
    }
}
```

---

## Security

- Webhook endpoints validate signatures via `X-Webhook-Secret` header
- Custom code execution can be disabled in config
- Authorization gate: `access-autobuilder`

---

## Testing

```bash
composer test
```

---

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

---

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

## Author

Created by [Jean-Marc Strauven (@Grazulex)](https://github.com/Grazulex)

### About the Author

Creator of 17+ Laravel packages with 6,000+ downloads, including:
- [laravel-devtoolbox](https://github.com/Grazulex/laravel-devtoolbox) - Swiss-army CLI for Laravel
- [laravel-apiroute](https://github.com/Grazulex/laravel-apiroute) - API versioning lifecycle management
- [laravel-arc](https://github.com/Grazulex/laravel-arc) - Eloquent attribute casting

**Need help with your Laravel project?** I offer consulting services:
- Custom package development
- Code audits and optimization
- Architecture consulting

Contact: [GitHub](https://github.com/Grazulex)
