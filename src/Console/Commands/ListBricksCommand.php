<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Console\Commands;

use Grazulex\AutoBuilder\Registry\BrickRegistry;
use Illuminate\Console\Command;

class ListBricksCommand extends Command
{
    protected $signature = 'autobuilder:list-bricks';

    protected $description = 'List all registered AutoBuilder bricks';

    public function handle(BrickRegistry $registry): int
    {
        $bricks = $registry->all();

        $this->info('Triggers:');
        $this->table(
            ['Name', 'Class', 'Category'],
            collect($bricks['triggers'])->map(fn ($b) => [$b['name'], $b['class'], $b['category']])->toArray()
        );

        $this->newLine();
        $this->info('Conditions:');
        $this->table(
            ['Name', 'Class', 'Category'],
            collect($bricks['conditions'])->map(fn ($b) => [$b['name'], $b['class'], $b['category']])->toArray()
        );

        $this->newLine();
        $this->info('Actions:');
        $this->table(
            ['Name', 'Class', 'Category'],
            collect($bricks['actions'])->map(fn ($b) => [$b['name'], $b['class'], $b['category']])->toArray()
        );

        return self::SUCCESS;
    }
}
