<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Wobqqq\Aegis\Aegis;
use Wobqqq\Aegis\Settings\AegisSetting;
use Wobqqq\AegisAdminIpAccess\AdminIpAccessModule;
use Wobqqq\AegisAdminIpAccess\Tests\Fixtures\Addresses;

/**
 * @return list<mixed>
 */
function listed(): array
{
    $ips = Aegis::settings(AdminIpAccessModule::KEY)['ips'] ?? null;

    return is_array($ips) ? array_values($ips) : [];
}

it('adds an address or a subnet from the console, in its canonical notation', function (string $ip, string $stored): void {
    whitelist();

    expect(Artisan::call('aegis:admin-ip-access:add-ip', ['ip' => $ip, '--note' => 'Home']))->toBe(0)
        ->and(Artisan::output())->toContain($stored . ' has been added to the whitelist.')
        ->and(listed())->toBe([['ip' => Addresses::ADMIN, 'note' => 'Office'], ['ip' => $stored, 'note' => 'Home']]);

    visitFrom('/nova/probe', Addresses::STRANGER)->assertStatus($stored === Addresses::STRANGER ? 200 : 403);
})->with([
    [Addresses::STRANGER, Addresses::STRANGER],
    [' 192.0.2.0/24 ', '192.0.2.0/24'],
    ['2001:DB8:0::/32', '2001:db8::/32'],
]);

it('lets a locked-out administrator back in from the console', function (): void {
    whitelist();
    visitFrom('/nova/probe', Addresses::STRANGER)->assertForbidden();

    Artisan::call('aegis:admin-ip-access:add-ip', ['ip' => Addresses::STRANGER]);

    visitFrom('/nova/probe', Addresses::STRANGER)->assertOk();
});

it('does not add an address or a subnet the whitelist already covers', function (string $ip): void {
    whitelist(['ips' => [['ip' => Addresses::ADMIN, 'note' => ''], ['ip' => '10.0.0.0/8', 'note' => ''], ['ip' => '2001:db8::1', 'note' => '']]]);

    expect(Artisan::call('aegis:admin-ip-access:add-ip', ['ip' => $ip]))->toBe(0)
        ->and(Artisan::output())->toContain('is already on the whitelist')
        ->and(listed())->toHaveCount(3);
})->with([Addresses::ADMIN, '10.1.2.3', '10.0.0.0/8', '10.20.0.0/16', '2001:0db8::0001']);

it('adds a subnet wider than a listed one', function (): void {
    whitelist(['ips' => [['ip' => '10.20.0.0/16', 'note' => '']]]);

    expect(Artisan::call('aegis:admin-ip-access:add-ip', ['ip' => '10.0.0.0/8']))->toBe(0)->and(listed())->toHaveCount(2);
});

it('refuses what is not an address or a subnet', function (string $ip): void {
    whitelist();

    expect(Artisan::call('aegis:admin-ip-access:add-ip', ['ip' => $ip]))->toBe(1)
        ->and(Artisan::output())->toContain('is not an IP address or a subnet')
        ->and(listed())->toHaveCount(1);
})->with(['not-an-ip', '10.0.0.0/33', '2001:db8::/129', '10.0.0.0/x', '10.0.0.0/', '300.1.1.1', '']);

it('reports a full whitelist instead of saving it', function (): void {
    whitelist(['ips' => array_map(static fn (int $i): array => ['ip' => '10.0.0.' . $i, 'note' => ''], range(1, 100))]);

    expect(Artisan::call('aegis:admin-ip-access:add-ip', ['ip' => Addresses::STRANGER]))->toBe(1)
        ->and(Artisan::output())->toContain('must not have more than 100 items')
        ->and(listed())->toHaveCount(100);
});

it('turns itself off from the console and keeps the whitelist', function (): void {
    whitelist();
    visitFrom('/nova/probe', Addresses::STRANGER)->assertForbidden();

    expect(Artisan::call('aegis:admin-ip-access:disable'))->toBe(0)
        ->and(Artisan::output())->toContain('Admin IP Access is off')
        ->and(Aegis::settings(AdminIpAccessModule::KEY))->toMatchArray(['enabled' => false, 'ips' => [['ip' => Addresses::ADMIN, 'note' => 'Office']]]);

    visitFrom('/nova/probe', Addresses::STRANGER)->assertOk();
});

it('turns itself off even when the stored values would fail the rules', function (): void {
    AegisSetting::query()->create(['section' => AdminIpAccessModule::KEY, 'values' => [
        'enabled' => true,
        'ips' => [['ip' => 'garbage'], ['ip' => Addresses::ADMIN, 'note' => ['x']], 'row'],
        'view' => '../secret',
    ]]);

    expect(Artisan::call('aegis:admin-ip-access:disable'))->toBe(0)
        ->and(Aegis::settings(AdminIpAccessModule::KEY))->toBe(['enabled' => false, 'ips' => [['ip' => Addresses::ADMIN, 'note' => '']], 'view' => AdminIpAccessModule::DEFAULT_VIEW]);
});

it("never sees an administrator's address in the console", function (): void {
    $module = new AdminIpAccessModule(static fn (): ?string => app()->runningInConsole() ? null : '192.0.2.1');

    expect($module->defaults())->toHaveKey('ips', [])
        ->and(resolve(AdminIpAccessModule::class)->defaults())->toHaveKey('ips', []);
});
