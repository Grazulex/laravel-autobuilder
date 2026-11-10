<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Tests\Fixtures\Bricks;

class NonRetryableFlakyAction extends FlakyAction
{
    public function canRetry(): bool
    {
        return false;
    }
}
