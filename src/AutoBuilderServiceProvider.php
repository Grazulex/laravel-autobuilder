<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder;

use Grazulex\AutoBuilder\Console\Commands\ClearCacheCommand;
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
use Grazulex\AutoBuilder\Trigger\ActiveFlowsCache;
use Grazulex\AutoBuilder\Trigger\TriggerManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AutoBuilderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/autobuilder.php', 'autobuilder');

        $this->app->singleton(BrickRegistry::class, fn ($app) => new BrickRegistry($app));

        $this->app->singleton(ActiveFlowsCache::class, fn ($app) => new ActiveFlowsCache($app->make('cache')));

        $this->app->singleton(TriggerManager::class, fn ($app) => new TriggerManager(
            $app->make(BrickRegistry::class),
            $app->make(ActiveFlowsCache::class),
        ));

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
                ClearCacheCommand::class,
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
        $this->app->booted(function (): void {
            $this->registerActiveFlowTriggers();
        });
    }

    /**
     * Register the triggers of active flows, in HTTP requests and, unless
     * autobuilder.triggers.console is false, in console processes (queue
     * workers, artisan commands, scheduler). Long-running processes read the
     * active flows once, so queue workers must be restarted after a flow change.
     */
    protected function registerActiveFlowTriggers(): void
    {
        if (! config('autobuilder.triggers.enabled', true)) {
            return;
        }

        $manager = $this->app->make(TriggerManager::class);

        if (! $this->app->runningInConsole()) {
            $manager->bootActiveFlows();

            return;
        }

        if (! config('autobuilder.triggers.console', true)) {
            return;
        }

        // A fresh install runs artisan commands (migrate...) before the flows
        // table exists: stay silent in that case.
        $manager->bootActiveFlowsIfInstalled();
    }

    protected function registerScheduledTasks(): void
    {
        if (! config('autobuilder.scheduling.enabled', true)) {
            return;
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('autobuilder:schedule-run')
                ->everyMinute()
                ->withoutOverlapping()
                ->runInBackground();
        });
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
