<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Triggers;

use Grazulex\AutoBuilder\Bricks\Trigger;
use Grazulex\AutoBuilder\Fields\Select;
use Grazulex\AutoBuilder\Fields\Text;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;

class OnQueueJobFailed extends Trigger
{
    public function name(): string
    {
        return 'Queue Job Failed';
    }

    public function description(): string
    {
        return 'Triggers when a queued job fails.';
    }

    public function icon(): string
    {
        return 'alert-triangle';
    }

    public function category(): string
    {
        return 'Application';
    }

    public function fields(): array
    {
        return [
            Select::make('job')
                ->label('Job Class (optional)')
                ->description('Leave empty to catch all failed jobs')
                ->options(fn () => $this->discoverJobs())
                ->searchable(),

            Text::make('queue')
                ->label('Queue Name (optional)')
                ->placeholder('default')
                ->description('Filter by specific queue'),
        ];
    }

    public function register(): void
    {
        $jobClass = $this->config('job');
        $queueName = $this->config('queue');

        Event::listen(JobFailed::class, function (JobFailed $event) use ($jobClass, $queueName) {
            $jobName = $event->job->resolveName();

            // Filter by job class
            if ($jobClass && $jobName !== $jobClass) {
                return;
            }

            // Filter by queue
            if ($queueName && $event->job->getQueue() !== $queueName) {
                return;
            }

            $this->dispatch([
                'job' => $jobName,
                'queue' => $event->job->getQueue(),
                'connection' => $event->connectionName,
                'exception' => [
                    'message' => $event->exception->getMessage(),
                    'code' => $event->exception->getCode(),
                    'file' => $event->exception->getFile(),
                    'line' => $event->exception->getLine(),
                ],
                'payload' => $event->job->payload(),
            ]);
        });
    }

    protected function discoverJobs(): array
    {
        $jobs = [];
        $path = app_path('Jobs');

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

                $class = 'App\\Jobs\\' . $relativePath;

                if (class_exists($class)) {
                    $jobs[$class] = class_basename($class);
                }
            }
        }

        return $jobs;
    }

    public function samplePayload(): array
    {
        return [
            'job' => 'App\\Jobs\\ProcessPayment',
            'queue' => 'default',
            'connection' => 'redis',
            'exception' => [
                'message' => 'Payment gateway timeout',
                'code' => 504,
                'file' => '/app/Jobs/ProcessPayment.php',
                'line' => 45,
            ],
            'payload' => [],
        ];
    }
}
