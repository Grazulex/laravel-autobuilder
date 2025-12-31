<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Tests;

use Grazulex\AutoBuilder\AutoBuilderServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            AutoBuilderServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('autobuilder.brick_paths', [
            __DIR__.'/../src/BuiltIn/Triggers',
            __DIR__.'/../src/BuiltIn/Conditions',
            __DIR__.'/../src/BuiltIn/Actions',
        ]);

        $app['config']->set('autobuilder.table_prefix', 'autobuilder_');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
