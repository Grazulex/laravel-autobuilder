<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Triggers;

use Grazulex\AutoBuilder\Bricks\Trigger;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Fields\Text;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;

/**
 * Trigger that fires when a user logs in.
 */
class OnLogin extends Trigger
{
    public function name(): string
    {
        return 'User Login';
    }

    public function description(): string
    {
        return 'Triggers when a user successfully logs in to the application.';
    }

    public function icon(): string
    {
        return 'log-in';
    }

    public function category(): string
    {
        return 'Authentication';
    }

    public function fields(): array
    {
        return [
            Select::make('guard')
                ->label('Auth Guard')
                ->description('Which authentication guard to listen to')
                ->options([
                    '' => 'All Guards',
                    'web' => 'Web',
                    'api' => 'API',
                ])
                ->default(''),

            Text::make('user_type')
                ->label('User Type (optional)')
                ->description('Only trigger for specific user class (e.g., App\\Models\\Admin)')
                ->placeholder('App\\Models\\User'),
        ];
    }

    public function register(): void
    {
        Event::listen(Login::class, function (Login $event) {
            $guardFilter = $this->config('guard', '');
            $userTypeFilter = $this->config('user_type', '');

            // Filter by guard if specified
            if ($guardFilter && $event->guard !== $guardFilter) {
                return;
            }

            // Filter by user type if specified
            if ($userTypeFilter && get_class($event->user) !== $userTypeFilter) {
                return;
            }

            $this->dispatch([
                'user' => $event->user->toArray(),
                'user_id' => $event->user->getKey(),
                'user_class' => get_class($event->user),
                'guard' => $event->guard,
                'remember' => $event->remember,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'logged_in_at' => now()->toIso8601String(),
            ]);
        });
    }

    public function samplePayload(): array
    {
        return [
            'user' => [
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
            'user_id' => 1,
            'user_class' => 'App\\Models\\User',
            'guard' => 'web',
            'remember' => false,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'logged_in_at' => now()->toIso8601String(),
        ];
    }
}
