<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Events;

use Illuminate\Foundation\Events\Dispatchable;

class TriggerDispatched
{
    use Dispatchable;

    public function __construct(
        public readonly string $flowId,
        public readonly string $trigger,
        public readonly array $payload
    ) {}
}
