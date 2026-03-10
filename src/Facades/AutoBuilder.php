<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Facades;

use Grazulex\AutoBuilder\Registry\BrickRegistry;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Grazulex\AutoBuilder\Registry\BrickRegistry register(string $class)
 * @method static array getTriggers()
 * @method static array getConditions()
 * @method static array getActions()
 * @method static array all()
 *
 * @see BrickRegistry
 */
class AutoBuilder extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'autobuilder';
    }
}
