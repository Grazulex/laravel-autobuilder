<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Registry;

use Grazulex\AutoBuilder\Bricks\Action;
use Grazulex\AutoBuilder\Bricks\Brick;
use Grazulex\AutoBuilder\Bricks\Condition;
use Grazulex\AutoBuilder\Bricks\Gate;
use Grazulex\AutoBuilder\Bricks\Trigger;
use Grazulex\AutoBuilder\Exceptions\BrickException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\File;
use ReflectionClass;

class BrickRegistry
{
    protected Application $app;

    protected array $triggers = [];

    protected array $conditions = [];

    protected array $actions = [];

    protected array $gates = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Register a brick class
     */
    public function register(string $class): static
    {
        if (! class_exists($class)) {
            throw new BrickException("Brick class not found: {$class}");
        }

        $reflection = new ReflectionClass($class);

        if ($reflection->isAbstract()) {
            return $this;
        }

        if ($reflection->isSubclassOf(Trigger::class)) {
            $this->triggers[$class] = $class;
        } elseif ($reflection->isSubclassOf(Condition::class)) {
            $this->conditions[$class] = $class;
        } elseif ($reflection->isSubclassOf(Action::class)) {
            $this->actions[$class] = $class;
        } elseif ($reflection->isSubclassOf(Gate::class)) {
            $this->gates[$class] = $class;
        }

        return $this;
    }

    /**
     * Discover and register bricks from configured paths
     */
    public function discover(): void
    {
        // Register built-in bricks
        if (config('autobuilder.built_in.triggers', true)) {
            $this->discoverInPath(__DIR__ . '/../BuiltIn/Triggers', 'Grazulex\\AutoBuilder\\BuiltIn\\Triggers');
        }

        if (config('autobuilder.built_in.conditions', true)) {
            $this->discoverInPath(__DIR__ . '/../BuiltIn/Conditions', 'Grazulex\\AutoBuilder\\BuiltIn\\Conditions');
        }

        if (config('autobuilder.built_in.actions', true)) {
            $this->discoverInPath(__DIR__ . '/../BuiltIn/Actions', 'Grazulex\\AutoBuilder\\BuiltIn\\Actions');
        }

        if (config('autobuilder.built_in.gates', true)) {
            $this->discoverInPath(__DIR__ . '/../BuiltIn/Gates', 'Grazulex\\AutoBuilder\\BuiltIn\\Gates');
        }

        // Discover custom bricks
        $paths = config('autobuilder.bricks.paths', [app_path('AutoBuilder')]);
        $namespaces = config('autobuilder.bricks.namespaces', ['App\\AutoBuilder']);

        foreach ($paths as $index => $path) {
            if (File::isDirectory($path)) {
                $namespace = $namespaces[$index] ?? 'App\\AutoBuilder';
                $this->discoverInPath($path, $namespace);
            }
        }
    }

    /**
     * Discover bricks in a specific path
     */
    protected function discoverInPath(string $path, string $namespace): void
    {
        if (! File::isDirectory($path)) {
            return;
        }

        $files = File::allFiles($path);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace(
                ['/', '.php'],
                ['\\', ''],
                $file->getRelativePathname()
            );

            $class = $namespace . '\\' . $relativePath;

            if (class_exists($class)) {
                $disabled = config('autobuilder.built_in.disabled', []);
                if (! in_array($class, $disabled)) {
                    $this->register($class);
                }
            }
        }
    }

    /**
     * Resolve a brick instance
     */
    public function resolve(string $class, array $config = []): Brick
    {
        if (! class_exists($class)) {
            throw new BrickException("Brick class not found: {$class}");
        }

        return new $class($config);
    }

    /**
     * Get all registered triggers
     */
    public function getTriggers(): array
    {
        return array_values(array_map(fn ($class) => $this->resolve($class)->toArray(), $this->triggers));
    }

    /**
     * Get all registered conditions
     */
    public function getConditions(): array
    {
        return array_values(array_map(fn ($class) => $this->resolve($class)->toArray(), $this->conditions));
    }

    /**
     * Get all registered actions
     */
    public function getActions(): array
    {
        return array_values(array_map(fn ($class) => $this->resolve($class)->toArray(), $this->actions));
    }

    /**
     * Get all registered gates
     */
    public function getGates(): array
    {
        return array_values(array_map(fn ($class) => $this->resolve($class)->toArray(), $this->gates));
    }

    /**
     * Get all registered bricks
     */
    public function all(): array
    {
        return [
            'triggers' => $this->getTriggers(),
            'conditions' => $this->getConditions(),
            'actions' => $this->getActions(),
            'gates' => $this->getGates(),
        ];
    }

    /**
     * Check if a brick class is registered
     */
    public function has(string $class): bool
    {
        return isset($this->triggers[$class])
            || isset($this->conditions[$class])
            || isset($this->actions[$class])
            || isset($this->gates[$class]);
    }
}
