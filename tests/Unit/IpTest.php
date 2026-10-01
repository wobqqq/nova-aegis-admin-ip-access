<?php

declare(strict_types=1);

use Wobqqq\AegisAdminIpAccess\AccessList;
use Wobqqq\AegisAdminIpAccess\Support\Ip;

it('writes an entry in its canonical notation', function (mixed $value, ?string $expected): void {
    expect(Ip::normalize($value))->toBe($expected);
})->with([
    ['192.0.2.1', '192.0.2.1'],
    [' 10.0.0.0/08 ', '10.0.0.0/8'],
    ['2001:0DB8:0000::0001', '2001:db8::1'],
    ['2001:db8::/32', '2001:db8::/32'],
    ['0.0.0.0/0', '0.0.0.0/0'],
    ['10.0.0.0/33', null],
    ['10.0.0.0/-1', null],
    ['10.0.0.0/0008', null],
    ['10.0.0.0/8/8', null],
    ['::1/129', null],
    ['10.0.0.256', null],
    ['example.com', null],
    ['', null],
    [42, null],
    [null, null],
]);

it('knows when a listed entry already covers another', function (string $entry, bool $covered): void {
    expect(Ip::covered($entry, ['10.0.0.0/8', '192.0.2.1', '2001:db8::/32']))->toBe($covered);
})->with([
    ['10.1.2.3', true],
    ['10.20.0.0/16', true],
    ['10.0.0.0/8', true],
    ['10.0.0.0/7', false],
    ['192.0.2.1', true],
    ['192.0.2.0/24', false],
    ['2001:db8:1::/48', true],
    ['2001:db9::1', false],
    ['::ffff:10.0.0.1', false],
]);

it('reads broken stored values as a safe list', function (): void {
    $list = AccessList::fromArray([
        'enabled' => 'yes',
        'ips' => [['ip' => '10.0.0.0/8'], ['ip' => '10.0.0.0/8'], ['ip' => 'nope'], ['ip' => ['array']], 'row', ['note' => 'no ip']],
        'view' => 'bad view!',
    ]);

    expect($list->enabled)->toBeTrue()
        ->and($list->subnets)->toBe(['10.0.0.0/8'])
        ->and($list->addresses)->toBe([])
        ->and($list->view)->toBe('aegis-admin-ip-access::denied')
        ->and(AccessList::fromArray(['ips' => 'not a list'])->isEmpty())->toBeTrue();
});

it('refuses a visitor without an address once the list applies', function (): void {
    $list = new AccessList(true, 'view', ['192.0.2.1' => true]);

    expect($list->allows(null))->toBeFalse()
        ->and($list->allows(''))->toBeFalse()
        ->and($list->allows('not an ip'))->toBeFalse()
        ->and($list->allows('192.0.2.1'))->toBeTrue();
});
