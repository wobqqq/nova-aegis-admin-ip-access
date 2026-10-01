<?php

declare(strict_types=1);

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Wobqqq\Aegis\Events\SettingsSaved;
use Wobqqq\AegisAdminIpAccess\AccessList;
use Wobqqq\AegisAdminIpAccess\AccessListStore;
use Wobqqq\AegisAdminIpAccess\Tests\Fixtures\Addresses;

use function Pest\Laravel\withServerVariables;

it('reads the list from the cache on a Nova request, without a database query', function (): void {
    whitelist();
    resolve(AccessListStore::class)->get();
    app()->forgetScopedInstances();

    $queries = 0;
    DB::listen(static function () use (&$queries): void {
        $queries++;
    });

    withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])->get('/nova/probe')->assertForbidden();

    expect($queries)->toBe(0)
        ->and(Cache::get(AccessListStore::CACHE_KEY))->toBe(['enabled' => true, 'view' => 'aegis-admin-ip-access::denied', 'addresses' => [Addresses::ADMIN], 'subnets' => []]);
});

it('clears its cache when the section is saved, and only then', function (): void {
    whitelist();
    resolve(AccessListStore::class)->get();

    expect(Cache::has(AccessListStore::CACHE_KEY))->toBeTrue();

    event(new SettingsSaved('hardening', []));
    expect(Cache::has(AccessListStore::CACHE_KEY))->toBeTrue();

    whitelist(['enabled' => false]);
    expect(Cache::has(AccessListStore::CACHE_KEY))->toBeFalse()
        ->and(resolve(AccessListStore::class)->get()->enabled)->toBeFalse();
});

it('rebuilds a cached list of another shape', function (mixed $cached): void {
    whitelist();
    Cache::put(AccessListStore::CACHE_KEY, $cached);
    app()->forgetScopedInstances();

    expect(resolve(AccessListStore::class)->get()->addresses)->toBe([Addresses::ADMIN => true]);
})->with([
    'a string' => ['broken'],
    'an old shape' => [['enabled' => true, 'exactIps' => []]],
    'bad addresses' => [['enabled' => true, 'view' => 'x', 'addresses' => [1], 'subnets' => []]],
    'bad subnets' => [['enabled' => true, 'view' => 'x', 'addresses' => [], 'subnets' => [null]]],
]);

it('keeps guarding when the cache store is down', function (): void {
    whitelist();

    /** @var CacheRepository&MockInterface $cache */
    $cache = Mockery::mock(CacheRepository::class);
    $cache->allows('get')->andThrow(new RuntimeException('cache down'));
    $cache->allows('put')->andThrow(new RuntimeException('cache down'));
    $cache->allows('forget')->andThrow(new RuntimeException('cache down'));
    $store = new AccessListStore($cache);

    expect($store->get())->toBeInstanceOf(AccessList::class)
        ->and($store->get()->allows(Addresses::STRANGER))->toBeFalse();

    $store->forget();
});

it('uses the cache store Aegis is configured with', function (): void {
    config(['aegis.cache_store' => 'file']);
    app()->forgetScopedInstances();

    whitelist();
    resolve(AccessListStore::class)->get();

    expect(Cache::store('file')->has(AccessListStore::CACHE_KEY))->toBeTrue();

    Cache::store('file')->forget(AccessListStore::CACHE_KEY);
});
