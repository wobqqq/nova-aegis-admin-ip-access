<?php

declare(strict_types=1);

arch('every file declares strict types')
    ->expect('Wobqqq\AegisAdminIpAccess')
    ->toUseStrictTypes();

arch('every class is final')
    ->expect('Wobqqq\AegisAdminIpAccess')
    ->classes()
    ->toBeFinal();

arch('no debugging calls are left behind')
    ->expect(['dd', 'dump', 'var_dump', 'print_r', 'ray', 'die', 'exit'])
    ->not->toBeUsed();

arch('value objects are immutable')
    ->expect([Wobqqq\AegisAdminIpAccess\AccessList::class, Wobqqq\AegisAdminIpAccess\AdminIpAccessModule::class])
    ->toBeReadonly();

arch('the module reads its settings through the Aegis core, never the table')
    ->expect('Wobqqq\AegisAdminIpAccess')
    ->not->toUse([Wobqqq\Aegis\Settings\AegisSetting::class, Illuminate\Support\Facades\DB::class, Illuminate\Database\Eloquent\Model::class]);

arch('the module never reads forwarded headers itself')
    ->expect('Wobqqq\AegisAdminIpAccess')
    ->not->toUse(['getallheaders', 'apache_request_headers']);

arch('the module opens no network connection')
    ->expect(['stream_socket_client', 'fsockopen', 'curl_init', 'file_get_contents', Illuminate\Support\Facades\Http::class])
    ->not->toBeUsedIn('Wobqqq\AegisAdminIpAccess');
