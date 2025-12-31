<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Events;

use Grazulex\AutoBuilder\Bricks\Brick;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Illuminate\Foundation\Events\Dispatchable;
use Throwable;

class BrickFailed
{
    use Dispatchable;

    public function __construct(
        public readonly Brick $brick,
        public readonly FlowContext $context,
        public readonly Throwable $exception
    ) {}
}
