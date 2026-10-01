<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Wobqqq\Aegis\Aegis;
use Wobqqq\AegisAdminIpAccess\AdminIpAccessModule;
use Wobqqq\AegisAdminIpAccess\Tests\Fixtures\Addresses;
use Wobqqq\AegisAdminIpAccess\Tests\Fixtures\User;
use Wobqqq\AegisAdminIpAccess\Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\withServerVariables;

pest()->extend(TestCase::class)->in('Unit', 'Feature');

function admin(): User
{
    return User::query()->create(['email' => 'admin@example.com', 'is_admin' => true]);
}

/**
 * Saves the section as the console does, with no administrator's address to keep on the list.
 *
 * @param array<string, mixed> $values
 *
 * @return array<string, mixed>
 */
function whitelist(array $values = []): array
{
    app()->instance('request', Request::create('/'));

    return Aegis::save(AdminIpAccessModule::KEY, $values + [
        'enabled' => true,
        'ips' => [['ip' => Addresses::ADMIN, 'note' => 'Office']],
        'view' => AdminIpAccessModule::DEFAULT_VIEW,
    ]);
}

/**
 * @return TestResponse<Response>
 */
function visitFrom(string $uri, string $ip, bool $json = false): TestResponse
{
    $request = withServerVariables(['REMOTE_ADDR' => $ip]);

    return $json ? $request->getJson($uri) : $request->get($uri);
}

/**
 * Calls the Aegis API as a signed-in administrator from the given address.
 *
 * @param array<string, mixed> $data
 *
 * @return TestResponse<Response>
 */
function asAdminFrom(string $ip, string $method, string $uri, array $data = []): TestResponse
{
    return actingAs(admin())->withServerVariables(['REMOTE_ADDR' => $ip])->json($method, $uri, $data);
}
