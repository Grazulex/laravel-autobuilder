<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Triggers;

use Grazulex\AutoBuilder\Bricks\Trigger;
use Grazulex\AutoBuilder\Fields\Select;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;

class OnEventDispatched extends Trigger
{
    public function name(): string
    {
        return 'Event Dispatched';
    }

    public function description(): string
    {
        return 'Triggers when a Laravel event is dispatched.';
    }

    public function icon(): string
    {
        return 'zap';
    }

    public function category(): string
    {
        return 'Application';
    }

    public function fields(): array
    {
        return [
            Select::make('event')
                ->label('Event')
                ->options(fn () => $this->discoverEvents())
                ->searchable()
                ->required(),
        ];
    }

    public function register(): void
    {
        $eventClass = $this->config('event');

        if (! $eventClass || ! class_exists($eventClass)) {
            return;
        }

        Event::listen($eventClass, function ($event) {
            $this->dispatch([
                'event' => get_class($event),
                'payload' => method_exists($event, 'toArray')
                    ? $event->toArray()
                    : get_object_vars($event),
            ]);
        });
    }

    protected function discoverEvents(): array
    {
        $events = [];
        $path = app_path('Events');

        if (File::isDirectory($path)) {
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

                $class = 'App\\Events\\' . $relativePath;

                if (class_exists($class)) {
                    $events[$class] = class_basename($class);
                }
            }
        }

        return $events;
    }

    public function samplePayload(): array
    {
        return [
            'event' => 'App\\Events\\UserRegistered',
            'payload' => [
                'user_id' => 1,
                'email' => 'user@example.com',
            ],
        ];
    }
}
