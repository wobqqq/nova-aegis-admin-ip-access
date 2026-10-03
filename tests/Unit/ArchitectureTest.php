<?php

declare(strict_types=1);
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Wobqqq\Aegis\Settings\AegisSetting;
use Wobqqq\AegisAdminIpAccess\AccessList;
use Wobqqq\AegisAdminIpAccess\AdminIpAccessModule;

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
    ->expect([AccessList::class, AdminIpAccessModule::class])
    ->toBeReadonly();

arch('the recovery actions are readonly and know nothing of the console')
    ->expect('Wobqqq\AegisAdminIpAccess\Actions')
    ->toBeReadonly()
    ->not->toUse(['Illuminate\Console', Request::class]);

arch('the module reads its settings through the Aegis core, never the table')
    ->expect('Wobqqq\AegisAdminIpAccess')
    ->not->toUse([AegisSetting::class, DB::class, Model::class]);

arch('the module never reads forwarded headers itself')
    ->expect('Wobqqq\AegisAdminIpAccess')
    ->not->toUse(['getallheaders', 'apache_request_headers']);

arch('the module opens no network connection')
    ->expect(['stream_socket_client', 'fsockopen', 'curl_init', 'file_get_contents', Http::class])
    ->not->toBeUsedIn('Wobqqq\AegisAdminIpAccess');
