<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Triggers;

use Grazulex\AutoBuilder\Bricks\Trigger;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Fields\Text;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;

/**
 * Trigger that fires when a user logs out.
 */
class OnLogout extends Trigger
{
    public function name(): string
    {
        return 'User Logout';
    }

    public function description(): string
    {
        return 'Triggers when a user logs out of the application.';
    }

    public function icon(): string
    {
        return 'log-out';
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
        Event::listen(Logout::class, function (Logout $event) {
            // User might be null in some cases
            if (! $event->user) {
                return;
            }

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
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'logged_out_at' => now()->toIso8601String(),
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
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'logged_out_at' => now()->toIso8601String(),
        ];
    }
}
