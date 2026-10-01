<?php

declare(strict_types=1);

use Wobqqq\AegisAdminIpAccess\Support\Message;

it('returns the translated line', function (): void {
    expect(Message::get('aegis-admin-ip-access::admin-ip-access.label'))->toBe(__('aegis-admin-ip-access::admin-ip-access.label'))
        ->and(Message::get('aegis-admin-ip-access::admin-ip-access.label'))->not->toBe('aegis-admin-ip-access::admin-ip-access.label');
});

it('answers the key itself for a key that names a group of lines', function (): void {
    expect(Message::get('aegis-admin-ip-access::admin-ip-access.fields'))->toBe('aegis-admin-ip-access::admin-ip-access.fields');
});
