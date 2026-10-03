<?php

declare(strict_types=1);

use Wobqqq\AegisAdminIpAccess\Actions\WhitelistEntry;

it('keeps an entry in its canonical notation with a trimmed note', function (): void {
    $entry = new WhitelistEntry(' 10.0.0.0/08 ', '  ' . str_repeat('n', 120));

    expect($entry->ip)->toBe('10.0.0.0/8')
        ->and($entry->note)->toBe(str_repeat('n', 100));
});

it('cannot hold something that is not an address or a subnet', function (): void {
    expect(static fn (): WhitelistEntry => new WhitelistEntry('10.0.0.0/33'))->toThrow(InvalidArgumentException::class);
});
