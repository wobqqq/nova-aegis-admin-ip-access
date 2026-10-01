<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use Wobqqq\Aegis\Aegis;
use Wobqqq\Aegis\Enums\Status;
use Wobqqq\Aegis\Modules\ModuleRegistry;
use Wobqqq\AegisAdminIpAccess\AdminIpAccessModule;
use Wobqqq\AegisAdminIpAccess\Tests\Fixtures\Addresses;

use function Pest\Laravel\withServerVariables;

/**
 * @param list<array{ip: string|null, note?: string|null}> $ips
 *
 * @return array<string, mixed>
 */
function section(array $ips, bool $enabled = true, string $view = AdminIpAccessModule::DEFAULT_VIEW): array
{
    return ['values' => ['enabled' => $enabled, 'ips' => array_map(static fn (array $row): array => $row + ['note' => ''], $ips), 'view' => $view]];
}

it('registers its section with Aegis', function (): void {
    $module = resolve(ModuleRegistry::class)->get(AdminIpAccessModule::KEY);

    expect($module)->toBeInstanceOf(AdminIpAccessModule::class)
        ->and(collect($module?->fields() ?? [])->pluck('name')->all())->toBe(['enabled', 'ips', 'view'])
        ->and(Aegis::settings(AdminIpAccessModule::KEY))->toBe(['enabled' => false, 'ips' => [], 'view' => AdminIpAccessModule::DEFAULT_VIEW]);
});

it('presets the administrator\'s own address and names it on the form', function (): void {
    asAdminFrom(Addresses::ADMIN, 'GET', '/nova-vendor/aegis/settings')
        ->assertOk()
        ->assertJsonPath('sections.2.key', AdminIpAccessModule::KEY)
        ->assertJsonPath('sections.2.values.ips', [['ip' => Addresses::ADMIN, 'note' => '']])
        ->assertJsonPath('sections.2.fields.1.help', 'IPv4 or IPv6 addresses and CIDR subnets, up to 100 entries. Your address is ' . Addresses::ADMIN . ': an enabled list has to include it.');
});

it('saves a list that covers the administrator, by address, subnet or another IPv6 notation', function (string $adminIp, string $listed): void {
    asAdminFrom($adminIp, 'PUT', '/nova-vendor/aegis/settings/admin-ip-access', section([['ip' => $listed, 'note' => 'Office'], ['ip' => null]]))
        ->assertOk()
        ->assertJsonPath('values.ips.0', ['ip' => $listed, 'note' => 'Office']);
})->with([
    [Addresses::ADMIN, Addresses::ADMIN],
    [Addresses::ADMIN, '198.51.100.0/24'],
    ['2001:db8::1', '2001:0DB8:0:0:0:0:0:1'],
    ['2001:db8::1', '2001:db8::/32'],
]);

it('refuses an enabled list that would lock out the administrator saving it', function (): void {
    asAdminFrom(Addresses::STRANGER, 'PUT', '/nova-vendor/aegis/settings/admin-ip-access', section([['ip' => Addresses::ADMIN]]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['ips' => 'The list does not include your address ' . Addresses::STRANGER . ': saving it would lock you out of Nova.']);

    expect(Aegis::settings(AdminIpAccessModule::KEY)['enabled'])->toBeFalse();
});

it('saves such a list while the module is off, or when the list is empty', function (bool $enabled, array $ips): void {
    /** @var list<array{ip: string|null}> $ips */
    asAdminFrom(Addresses::ADMIN, 'PUT', '/nova-vendor/aegis/settings/admin-ip-access', section($ips, $enabled))->assertOk();
})->with([
    'off' => [false, [['ip' => Addresses::STRANGER]]],
    'empty' => [true, []],
    'only blank rows' => [true, [['ip' => null]]],
]);

it('refuses what is not an address, a subnet or a view name', function (array $payload, string $error): void {
    /** @var array<string, mixed> $payload */
    asAdminFrom(Addresses::ADMIN, 'PUT', '/nova-vendor/aegis/settings/admin-ip-access', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($error);
})->with([
    'a word' => [section([['ip' => Addresses::ADMIN], ['ip' => 'not-an-ip']]), 'ips.1.ip'],
    'a mask too wide' => [section([['ip' => Addresses::ADMIN], ['ip' => '10.0.0.0/33']]), 'ips.1.ip'],
    'an IPv6 mask too wide' => [section([['ip' => Addresses::ADMIN], ['ip' => '2001:db8::/129']]), 'ips.1.ip'],
    'an octet out of range' => [section([['ip' => Addresses::ADMIN], ['ip' => '300.1.1.1']]), 'ips.1.ip'],
    'markup' => [section([['ip' => Addresses::ADMIN], ['ip' => '<script>alert(1)</script>']]), 'ips.1.ip'],
    'an unknown column' => [['values' => ['enabled' => true, 'ips' => [['ip' => Addresses::ADMIN, 'note' => '', 'role' => 'x']], 'view' => AdminIpAccessModule::DEFAULT_VIEW]], 'ips.0'],
    'a long note' => [section([['ip' => Addresses::ADMIN, 'note' => str_repeat('n', 101)]]), 'ips.0.note'],
    'a path in the view' => [section([['ip' => Addresses::ADMIN]], view: '../../etc/passwd'), 'view'],
    'no list' => [['values' => ['enabled' => true, 'view' => AdminIpAccessModule::DEFAULT_VIEW]], 'ips'],
    'too many rows' => [section(array_map(static fn (int $i): array => ['ip' => '10.0.' . intdiv($i, 250) . '.' . ($i % 250)], range(0, 100))), 'ips'],
]);

it('shows the bad value as text in the error', function (): void {
    asAdminFrom(Addresses::ADMIN, 'PUT', '/nova-vendor/aegis/settings/admin-ip-access', section([['ip' => Addresses::ADMIN], ['ip' => '<b>x</b>']]))
        ->assertJsonPath('errors', ['ips.1.ip' => ['"<b>x</b>" is not an IP address or a subnet such as 192.0.2.0/24.']]);
});

it('refuses a save from a stranger before the controller is reached', function (): void {
    whitelist();

    withServerVariables(['REMOTE_ADDR' => Addresses::STRANGER])->putJson('/nova-vendor/aegis/settings/admin-ip-access', section([]))->assertForbidden();

    expect(Aegis::settings(AdminIpAccessModule::KEY)['enabled'])->toBeTrue();
});

it('reports its state on the dashboard', function (array $values, Status $status, string $message): void {
    /** @var array<string, mixed> $values */
    $result = resolve(ModuleRegistry::class)->getOrFail(AdminIpAccessModule::KEY)->status($values);

    expect($result?->status)->toBe($status)->and($result?->message)->toBe($message);
})->with([
    'off' => [['enabled' => false, 'ips' => [['ip' => Addresses::ADMIN]]], Status::WARN, 'Admin IP Access is off: Nova is open to every address.'],
    'empty' => [['enabled' => true, 'ips' => [['ip' => '']]], Status::WARN, 'Admin IP Access is on, but the list is empty: every address is let in.'],
    'one' => [['enabled' => true, 'ips' => [['ip' => Addresses::ADMIN]]], Status::PASS, 'Nova is open to one address or subnet only.'],
    'several' => [['enabled' => true, 'ips' => [['ip' => Addresses::ADMIN], ['ip' => '10.0.0.0/8']]], Status::PASS, 'Nova is open to 2 addresses and subnets only.'],
]);

it('refuses invalid values saved from the console too', function (): void {
    whitelist(['ips' => [['ip' => 'nope', 'note' => '']]]);
})->throws(ValidationException::class);
