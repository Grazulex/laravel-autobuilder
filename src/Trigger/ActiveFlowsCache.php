<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Trigger;

use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Support\AutoBuilderLogger;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

/**
 * Caches the list of active flows used to register triggers on every request.
 *
 * Only the raw database attributes of each flow are stored (plain arrays of
 * scalars), never Eloquent models or closures, so the payload is safe for any
 * cache store. Models are re-hydrated from those attributes without a query.
 */
class ActiveFlowsCache
{
    public const DEFAULT_KEY = 'autobuilder:active_flows';

    public function __construct(
        protected CacheFactory $cache
    ) {}

    public function enabled(): bool
    {
        return (bool) config('autobuilder.cache.enabled', true);
    }

    public function key(): string
    {
        $key = config('autobuilder.cache.key');

        return is_string($key) && $key !== '' ? $key : self::DEFAULT_KEY;
    }

    /**
     * Time to live in seconds, or null to cache until invalidated.
     */
    public function ttl(): ?int
    {
        $ttl = config('autobuilder.cache.ttl');

        if ($ttl === null || $ttl === '' || (int) $ttl <= 0) {
            return null;
        }

        return (int) $ttl;
    }

    /**
     * Get the active flows, from the cache when possible.
     *
     * A cache failure falls back to the database. A database failure is
     * thrown to the caller, and nothing is cached in that case.
     *
     * @return Collection<int, Flow>
     */
    public function get(): Collection
    {
        if (! $this->enabled()) {
            return Flow::active()->get();
        }

        $store = null;

        try {
            $store = $this->store();
            $cached = $store->get($this->key());

            if ($this->isValidPayload($cached)) {
                return Flow::hydrate($cached);
            }
        } catch (Throwable $e) {
            AutoBuilderLogger::warning("[AutoBuilder] Could not read active flows from cache, falling back to the database: {$e->getMessage()}");
            $store = null;
        }

        $flows = Flow::active()->get();

        if ($store instanceof Repository) {
            $this->put($store, $flows);
        }

        return $flows;
    }

    /**
     * Remove the cached active flows. Returns false if the cache store failed.
     */
    public function forget(): bool
    {
        try {
            $this->store()->forget($this->key());

            return true;
        } catch (Throwable $e) {
            AutoBuilderLogger::warning("[AutoBuilder] Could not clear the active flows cache: {$e->getMessage()}");

            return false;
        }
    }

    protected function store(): Repository
    {
        $store = config('autobuilder.cache.store');

        return $this->cache->store(is_string($store) && $store !== '' ? $store : null);
    }

    /**
     * @param  Collection<int, Flow>  $flows
     */
    protected function put(Repository $store, Collection $flows): void
    {
        $payload = $flows->map(fn (Flow $flow): array => $flow->getAttributes())->values()->all();

        try {
            $ttl = $this->ttl();

            if ($ttl === null) {
                $store->forever($this->key(), $payload);
            } else {
                $store->put($this->key(), $payload, $ttl);
            }
        } catch (Throwable $e) {
            AutoBuilderLogger::warning("[AutoBuilder] Could not write active flows to cache: {$e->getMessage()}");
        }
    }

    protected function isValidPayload(mixed $payload): bool
    {
        if (! is_array($payload) || ! array_is_list($payload)) {
            return false;
        }

        foreach ($payload as $attributes) {
            if (! is_array($attributes) || ! isset($attributes['id'])) {
                return false;
            }
        }

        return true;
    }
}
