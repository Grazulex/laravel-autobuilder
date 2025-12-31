<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Console\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'autobuilder:install';

    protected $description = 'Install the AutoBuilder package';

    public function handle(): int
    {
        $this->info('Installing AutoBuilder...');

        // Publish config
        $this->call('vendor:publish', [
            '--tag' => 'autobuilder-config',
        ]);

        // Publish migrations
        $this->call('vendor:publish', [
            '--tag' => 'autobuilder-migrations',
        ]);

        // Run migrations
        if ($this->confirm('Run migrations now?', true)) {
            $this->call('migrate');
        }

        $this->info('AutoBuilder installed successfully!');
        $this->info('Access the builder at: ' . url('/autobuilder'));

        return self::SUCCESS;
    }
}
