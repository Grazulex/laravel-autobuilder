<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Support;

use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

class AutoBuilderLogger
{
    protected static function isEnabled(): bool
    {
        return (bool) config('autobuilder.logging.activated', true);
    }

    protected static function logger(): LogManager|LoggerInterface
    {
        $channel = config('autobuilder.logging.channel', 'stack');

        return Log::channel($channel);
    }

    public static function debug(string $message, array $context = []): void
    {
        if (self::isEnabled()) {
            self::logger()->debug($message, $context);
        }
    }

    public static function info(string $message, array $context = []): void
    {
        if (self::isEnabled()) {
            self::logger()->info($message, $context);
        }
    }

    public static function warning(string $message, array $context = []): void
    {
        if (self::isEnabled()) {
            self::logger()->warning($message, $context);
        }
    }

    public static function error(string $message, array $context = []): void
    {
        if (self::isEnabled()) {
            self::logger()->error($message, $context);
        }
    }
}
