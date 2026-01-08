<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder;

use Grazulex\AutoBuilder\Console\Commands\InstallCommand;
use Grazulex\AutoBuilder\Console\Commands\ListBricksCommand;
use Grazulex\AutoBuilder\Console\Commands\MakeBrickCommand;
use Grazulex\AutoBuilder\Console\Commands\RunFlowCommand;
use Grazulex\AutoBuilder\Console\Commands\ScheduleRunCommand;
use Grazulex\AutoBuilder\Events\TriggerDispatched;
use Grazulex\AutoBuilder\Http\Middleware\AutoBuilderAuth;
use Grazulex\AutoBuilder\Listeners\TriggerDispatchedListener;
use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Observers\FlowObserver;
use Grazulex\AutoBuilder\Policies\FlowPolicy;
use Grazulex\AutoBuilder\Registry\BrickRegistry;
use Grazulex\AutoBuilder\Trigger\TriggerManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class AutoBuilderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/autobuilder.php', 'autobuilder');

        $this->app->singleton(BrickRegistry::class, function ($app) {
            return new BrickRegistry($app);
        });

        $this->app->singleton(TriggerManager::class, function ($app) {
            return new TriggerManager($app->make(BrickRegistry::class));
        });

        $this->app->alias(BrickRegistry::class, 'autobuilder');
    }

    public function boot(): void
    {
        $this->registerMiddleware();
        $this->registerPublishables();
        $this->registerCommands();
        $this->registerRoutes();
        $this->registerMigrations();
        $this->registerViews();
        $this->discoverBricks();
        $this->registerEventListeners();
        $this->registerModelObservers();
        $this->registerPolicies();
        $this->configureRateLimiting();
        $this->registerScheduledTasks();
        $this->bootTriggers();
    }

    protected function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('autobuilder.auth', AutoBuilderAuth::class);
    }

    protected function registerPublishables(): void
    {
        if ($this->app->runningInConsole()) {
            // Config
            $this->publishes([
                __DIR__.'/../config/autobuilder.php' => config_path('autobuilder.php'),
            ], 'autobuilder-config');

            // Migrations
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'autobuilder-migrations');

            // Views
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/autobuilder'),
            ], 'autobuilder-views');

            // Compiled Assets (JS/CSS)
            $this->publishes([
                __DIR__.'/../resources/dist' => public_path('vendor/autobuilder'),
            ], 'autobuilder-assets');

            // All assets
            $this->publishes([
                __DIR__.'/../config/autobuilder.php' => config_path('autobuilder.php'),
                __DIR__.'/../database/migrations' => database_path('migrations'),
                __DIR__.'/../resources/views' => resource_path('views/vendor/autobuilder'),
                __DIR__.'/../resources/dist' => public_path('vendor/autobuilder'),
            ], 'autobuilder');
        }
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                MakeBrickCommand::class,
                RunFlowCommand::class,
                ListBricksCommand::class,
                ScheduleRunCommand::class,
            ]);
        }
    }

    protected function registerRoutes(): void
    {
        if (config('autobuilder.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }
    }

    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'autobuilder');
    }

    protected function discoverBricks(): void
    {
        if (config('autobuilder.bricks.discover', true)) {
            $this->app->make(BrickRegistry::class)->discover();
        }
    }

    protected function registerEventListeners(): void
    {
        Event::listen(TriggerDispatched::class, TriggerDispatchedListener::class);
    }

    protected function registerModelObservers(): void
    {
        Flow::observe($this->app->make(FlowObserver::class));
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Flow::class, FlowPolicy::class);
    }

    protected function bootTriggers(): void
    {
        // Only boot triggers if not running in console (migrations, etc.)
        // and triggers are enabled
        if (! $this->app->runningInConsole() && config('autobuilder.triggers.enabled', true)) {
            $this->app->booted(function () {
                $this->app->make(TriggerManager::class)->bootActiveFlows();
            });
        }
    }

    protected function configureRateLimiting(): void
    {
        if (! config('autobuilder.rate_limiting.enabled', true)) {
            return;
        }

        RateLimiter::for('autobuilder-webhooks', function (Request $request) {
            $maxAttempts = config('autobuilder.rate_limiting.webhooks.max_attempts', 60);
            $decayMinutes = config('autobuilder.rate_limiting.webhooks.decay_minutes', 1);

            return Limit::perMinutes($decayMinutes, $maxAttempts)
                ->by($request->ip());
        });
    }
}
