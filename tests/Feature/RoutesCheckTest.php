<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Checks\CheckRunner;
use Wobqqq\Aegis\Enums\Status;
use Wobqqq\AegisAdminIpAccess\Checks\NovaRoutesCheck;

it('passes while every Nova route goes through the check', function (): void {
    whitelist();

    expect(resolve(NovaRoutesCheck::class)->run()->status)->toBe(Status::PASS);
});

it("names the Nova routes registered outside Nova's middleware", function (): void {
    whitelist();
    Route::middleware('web')->get('/nova-vendor/rogue-tool/data', static fn (): string => 'rogue');
    Route::middleware('web')->get('/nova/rogue-page', static fn (): string => 'rogue');

    $result = resolve(NovaRoutesCheck::class)->run();

    expect($result->status)->toBe(Status::FAIL)
        ->and($result->message)->toBe("2 Nova routes skip the address check: /nova-vendor/rogue-tool/data, /nova/rogue-page. Register them with Nova's middleware groups.");
});

it('only reports while the module is on', function (): void {
    expect(resolve(NovaRoutesCheck::class)->run()->status)->toBe(Status::INFO);
});

it('runs with the Aegis checks', function (): void {
    whitelist();

    $keys = array_map(static fn (CheckResult $result): string => $result->key, resolve(CheckRunner::class)->checks());

    expect($keys)->toContain('admin_ip_access_routes')
        ->and(Artisan::call('aegis:check'))->toBeInt();
});
