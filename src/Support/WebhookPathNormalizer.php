<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Support;

use Illuminate\Support\Str;

class WebhookPathNormalizer
{
    /**
     * Normalize a webhook path for consistent storage and matching.
     *
     * - Trims whitespace
     * - Converts to lowercase
     * - Slugifies each path segment
     * - Trims leading/trailing slashes
     * - Collapses multiple slashes
     */
    public static function normalize(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $path = trim($path);
        $path = mb_strtolower($path);

        // Remove leading/trailing slashes
        $path = trim($path, '/');

        // Split into segments and slugify each
        $segments = array_filter(explode('/', $path), fn (string $s) => $s !== '');
        $segments = array_map(fn (string $s) => Str::slug($s), $segments);

        return implode('/', $segments);
    }
}
