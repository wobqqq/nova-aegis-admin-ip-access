<?php

declare(strict_types=1);

namespace Wobqqq\AegisAdminIpAccess;

use Illuminate\Contracts\Cache\Repository as Cache;
use Throwable;
use Wobqqq\Aegis\Aegis;

/**
 * Keeps the parsed whitelist in the cache, so a Nova request costs one cache read and no query.
 */
final class AccessListStore
{
    /**
     * Part of the cache key: a release that changes what is cached bumps it.
     */
    public const CACHE_KEY = 'aegis.admin-ip-access.v1';

    private const TTL = 3600;

    private ?AccessList $list = null;

    public function __construct(private readonly Cache $cache)
    {
    }

    public function get(): AccessList
    {
        return $this->list ??= $this->load();
    }

    public function forget(): void
    {
        $this->list = null;

        try {
            $this->cache->forget(self::CACHE_KEY);
        } catch (Throwable) {
            // An unreachable cache store holds nothing to forget.
        }
    }

    private function load(): AccessList
    {
        try {
            $list = AccessList::fromCache($this->cache->get(self::CACHE_KEY));
        } catch (Throwable) {
            $list = null;
        }

        if ($list instanceof AccessList) {
            return $list;
        }

        $list = AccessList::fromArray(Aegis::settings(AdminIpAccessModule::KEY));

        try {
            $this->cache->put(self::CACHE_KEY, $list->toCache(), self::TTL);
        } catch (Throwable) {
            // The list is still applied; the next request tries the cache again.
        }

        return $list;
    }
}
