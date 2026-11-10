<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Tests\Fixtures\Bricks;

class CustomRetryFlakyAction extends FlakyAction
{
    public function maxRetries(): int
    {
        return 1;
    }

    public function retryDelay(): int
    {
        return 5;
    }
}
