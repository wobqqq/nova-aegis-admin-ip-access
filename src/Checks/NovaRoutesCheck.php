<?php

declare(strict_types=1);

namespace Wobqqq\AegisAdminIpAccess\Checks;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Laravel\Nova\Nova;
use Wobqqq\Aegis\Aegis;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Contracts\Check;
use Wobqqq\AegisAdminIpAccess\AccessList;
use Wobqqq\AegisAdminIpAccess\AdminIpAccessModule;
use Wobqqq\AegisAdminIpAccess\Http\Middleware\RestrictNovaAccess;

/**
 * Finds the Nova routes a package or the application registered outside Nova's middleware groups.
 */
final readonly class NovaRoutesCheck implements Check
{
    private const KEY = 'admin_ip_access_routes';

    public function __construct(private Router $router)
    {
    }

    public function run(): CheckResult
    {
        $label = (string)__('aegis-admin-ip-access::admin-ip-access.checks.routes.label');

        if (!AccessList::fromArray(Aegis::settings(AdminIpAccessModule::KEY))->enabled) {
            return CheckResult::info(self::KEY, $label, (string)__('aegis-admin-ip-access::admin-ip-access.checks.routes.off'));
        }

        $unguarded = [];

        foreach ($this->router->getRoutes()->getRoutes() as $route) {
            if ($this->isNova($route) && !in_array(RestrictNovaAccess::class, $this->router->gatherRouteMiddleware($route), true)) {
                $unguarded[] = '/' . ltrim($route->uri(), '/');
            }
        }

        if ($unguarded === []) {
            return CheckResult::pass(self::KEY, $label, (string)__('aegis-admin-ip-access::admin-ip-access.checks.routes.pass'));
        }

        return CheckResult::fail(self::KEY, $label, (string)__('aegis-admin-ip-access::admin-ip-access.checks.routes.fail', [
            'count' => count($unguarded),
            'routes' => implode(', ', array_slice($unguarded, 0, 5)),
        ]));
    }

    private function isNova(Route $route): bool
    {
        $uri = trim($route->uri(), '/');
        $path = trim(Nova::path(), '/');

        return str_starts_with($uri . '/', 'nova-api/')
            || str_starts_with($uri . '/', 'nova-vendor/')
            || ($path !== '' && ($uri === $path || str_starts_with($uri, $path . '/')));
    }
}
