<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Events;

use Grazulex\AutoBuilder\Flow\FlowContext;
use Grazulex\AutoBuilder\Models\Flow;
use Illuminate\Foundation\Events\Dispatchable;

class FlowCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly Flow $flow,
        public readonly FlowContext $context
    ) {}
}
