<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeBrickCommand extends Command
{
    protected $signature = 'autobuilder:make-brick {name} {--type=action : The type of brick (trigger, condition, action)}';

    protected $description = 'Create a new AutoBuilder brick';

    public function handle(): int
    {
        $name = $this->argument('name');
        $type = $this->option('type');

        if (! in_array($type, ['trigger', 'condition', 'action'])) {
            $this->error('Invalid type. Must be: trigger, condition, or action');

            return self::FAILURE;
        }

        $directory = app_path('AutoBuilder/' . ucfirst($type) . 's');
        $path = $directory . '/' . $name . '.php';

        if (File::exists($path)) {
            $this->error("Brick already exists: {$path}");

            return self::FAILURE;
        }

        File::ensureDirectoryExists($directory);

        $stub = $this->getStub($type);
        $content = str_replace(
            ['{{name}}', '{{namespace}}'],
            [$name, 'App\\AutoBuilder\\' . ucfirst($type) . 's'],
            $stub
        );

        File::put($path, $content);

        $this->info("Brick created: {$path}");

        return self::SUCCESS;
    }

    protected function getStub(string $type): string
    {
        $baseClass = match ($type) {
            'trigger' => 'Trigger',
            'condition' => 'Condition',
            'action' => 'Action',
        };

        $method = match ($type) {
            'trigger' => 'public function register(): void
    {
        // Register event listeners here
    }',
            'condition' => 'public function evaluate(FlowContext $context): bool
    {
        // Evaluate condition and return true/false
        return true;
    }',
            'action' => 'public function handle(FlowContext $context): FlowContext
    {
        // Perform action here

        return $context->log(\'Action completed\');
    }',
        };

        return <<<PHP
<?php

declare(strict_types=1);

namespace {{namespace}};

use Grazulex\AutoBuilder\Bricks\\{$baseClass};
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Flow\FlowContext;

class {{name}} extends {$baseClass}
{
    public function name(): string
    {
        return '{{name}}';
    }

    public function description(): string
    {
        return 'Description of your brick';
    }

    public function icon(): string
    {
        return 'box'; // Lucide icon name
    }

    public function category(): string
    {
        return 'Custom';
    }

    public function fields(): array
    {
        return [
            Text::make('example')
                ->label('Example Field')
                ->description('An example field')
                ->required(),
        ];
    }

    {$method}
}
PHP;
    }
}
