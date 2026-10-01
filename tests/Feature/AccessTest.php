<?php

declare(strict_types=1);

use Illuminate\Support\Facades\View;
use Wobqqq\AegisAdminIpAccess\Http\Middleware\RestrictNovaAccess;
use Wobqqq\AegisAdminIpAccess\Tests\Fixtures\Addresses;

it('lets in the listed addresses, subnets and other IPv6 notations of a listed address', function (string $ip): void {
    whitelist(['ips' => [['ip' => Addresses::ADMIN, 'note' => ''], ['ip' => '10.0.0.0/8', 'note' => ''], ['ip' => '2001:db8::1', 'note' => ''], ['ip' => '2001:db8:ff::/48', 'note' => ''], ['ip' => null, 'note' => null]]]);

    visitFrom('/nova/probe', $ip)->assertOk()->assertSee('nova page');
})->with([Addresses::ADMIN, '10.20.30.40', '2001:db8::1', '2001:0db8:0000:0000:0000:0000:0000:0001', '2001:db8:ff:1::5']);

it('answers any other address 403 with the denied page, on every kind of Nova route', function (string $uri): void {
    whitelist();

    visitFrom($uri, Addresses::STRANGER)
        ->assertForbidden()
        ->assertSee('Access denied')
        ->assertSee('Your address: ' . Addresses::STRANGER)
        ->assertHeader('Cache-Control', 'no-store, private');
})->with(['/nova/probe', '/nova/sign-in-probe', '/nova/early-probe', '/nova-vendor/aegis/settings', '/nova-api/users']);

it('answers JSON to the Nova API and the tools', function (string $uri): void {
    whitelist();

    visitFrom($uri, Addresses::STRANGER, json: true)->assertForbidden()->assertExactJson(['message' => 'Your address is not allowed to open this page.']);
})->with(['/nova-api/users', '/nova-vendor/aegis/overview']);

it('leaves the rest of the application alone', function (): void {
    whitelist();

    visitFrom('/welcome', Addresses::STRANGER)->assertOk()->assertSee('site');
});

it('lets everyone in while it is off', function (): void {
    whitelist(['enabled' => false]);

    visitFrom('/nova/probe', Addresses::STRANGER)->assertOk();
});

it('lets everyone in while the list is empty rather than lock every administrator out', function (): void {
    whitelist(['ips' => []]);

    visitFrom('/nova/probe', Addresses::STRANGER)->assertOk();
});

it('lets everyone in before anything is saved', function (): void {
    visitFrom('/nova/probe', Addresses::STRANGER)->assertOk();
});

it('answers with the configured view, and with its own page when that view is missing or broken', function (): void {
    View::addNamespace('acme', __DIR__ . '/../Fixtures/views');

    whitelist(['view' => 'acme::closed']);
    visitFrom('/nova/probe', Addresses::STRANGER)->assertForbidden()->assertSee('Closed for &lt;you&gt;', false);

    whitelist(['view' => 'acme::missing']);
    visitFrom('/nova/probe', Addresses::STRANGER)->assertForbidden()->assertSee('Access denied');

    whitelist(['view' => 'acme::broken']);
    visitFrom('/nova/probe', Addresses::STRANGER)->assertForbidden()->assertSee('Access denied');
});

it('applies a saved list at once, without waiting for the cache to expire', function (): void {
    whitelist();
    visitFrom('/nova/probe', Addresses::STRANGER)->assertForbidden();

    whitelist(['ips' => [['ip' => Addresses::ADMIN, 'note' => ''], ['ip' => Addresses::STRANGER, 'note' => '']]]);

    visitFrom('/nova/probe', Addresses::STRANGER)->assertOk();
});

it('sits in front of every Nova middleware group and config list', function (): void {
    $groups = resolve(Illuminate\Routing\Router::class)->getMiddlewareGroups();

    foreach (['nova', 'nova:api', 'nova:auth', 'nova:serving'] as $group) {
        expect(data_get($groups, $group . '.0'))->toBe(RestrictNovaAccess::class);
    }

    expect(config('nova.middleware'))->toContain(RestrictNovaAccess::class)
        ->and(config('nova.api_middleware'))->toContain(RestrictNovaAccess::class);
});

it('checks a request once however many Nova groups it passes', function (): void {
    whitelist();
    $middleware = resolve(RestrictNovaAccess::class);
    $request = Illuminate\Http\Request::create('/nova/probe', server: ['REMOTE_ADDR' => Addresses::STRANGER]);
    $next = static fn (): Symfony\Component\HttpFoundation\Response => new Illuminate\Http\Response('next');

    expect($middleware->handle($request, $next)->getStatusCode())->toBe(403);

    $request->attributes->set('aegis.admin-ip-access.checked', true);

    expect($middleware->handle($request, $next)->getContent())->toBe('next');
});
