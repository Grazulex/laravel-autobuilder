<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Console\Commands;

use Grazulex\AutoBuilder\Trigger\ActiveFlowsCache;
use Illuminate\Console\Command;

class ClearCacheCommand extends Command
{
    protected $signature = 'autobuilder:clear-cache';

    protected $description = 'Clear the cached list of active AutoBuilder flows';

    public function handle(ActiveFlowsCache $cache): int
    {
        if (! $cache->forget()) {
            $this->error('Could not clear the active flows cache. Check the configured cache store.');

            return self::FAILURE;
        }

        $this->info('AutoBuilder active flows cache cleared.');

        return self::SUCCESS;
    }
}
