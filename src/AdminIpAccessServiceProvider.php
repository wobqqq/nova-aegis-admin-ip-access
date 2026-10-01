<?php

declare(strict_types=1);

namespace Wobqqq\AegisAdminIpAccess;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Override;
use Wobqqq\Aegis\Aegis;
use Wobqqq\Aegis\Events\SettingsSaved;
use Wobqqq\AegisAdminIpAccess\Checks\NovaRoutesCheck;
use Wobqqq\AegisAdminIpAccess\Console\AddIpCommand;
use Wobqqq\AegisAdminIpAccess\Console\DisableCommand;
use Wobqqq\AegisAdminIpAccess\Http\Middleware\RestrictNovaAccess;

final class AdminIpAccessServiceProvider extends ServiceProvider
{
    /**
     * Every Nova route passes one of these groups: pages and tools through `nova` and `nova:serving`,
     * the API and assets through `nova:api`, sign-in through `nova:auth`.
     */
    public const array NOVA_GROUPS = ['nova', 'nova:api', 'nova:auth', 'nova:serving'];

    public const array NOVA_CONFIG = ['nova.middleware', 'nova.api_middleware'];

    #[Override]
    public function register(): void
    {
        $this->app->singleton(AdminIpAccessModule::class, static fn (Application $app): AdminIpAccessModule => new AdminIpAccessModule(
            static fn (): ?string => $app->runningInConsole() ? null : $app->make(Request::class)->ip(),
        ));

        $this->app->scoped(AccessListStore::class, static function (Application $app): AccessListStore {
            $store = $app->make(Config::class)->get('aegis.cache_store');

            return new AccessListStore($app->make(CacheFactory::class)->store(is_string($store) && $store !== '' ? $store : null));
        });
    }

    public function boot(Dispatcher $events): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'aegis-admin-ip-access');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'aegis-admin-ip-access');

        Aegis::module($this->app->make(AdminIpAccessModule::class));
        Aegis::check($this->app->make(NovaRoutesCheck::class));

        $events->listen(SettingsSaved::class, function (SettingsSaved $event): void {
            if ($event->section === AdminIpAccessModule::KEY) {
                $this->app->make(AccessListStore::class)->forget();
            }
        });

        $this->app->booted(function (): void {
            $this->guardNova();
        });

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../resources/views' => resource_path('views/vendor/aegis-admin-ip-access')], 'aegis-admin-ip-access-views');
            $this->commands([AddIpCommand::class, DisableCommand::class]);
        }
    }

    /**
     * Runs once every provider has booted, so Nova's groups and config exist and are not rebuilt over it.
     */
    private function guardNova(): void
    {
        $router = $this->app->make(Router::class);

        foreach (self::NOVA_GROUPS as $group) {
            if ($router->hasMiddlewareGroup($group)) {
                $router->prependMiddlewareToGroup($group, RestrictNovaAccess::class);
            }
        }

        $config = $this->app->make(Config::class);

        foreach (self::NOVA_CONFIG as $key) {
            $middleware = $config->get($key);

            if (is_array($middleware) && !in_array(RestrictNovaAccess::class, $middleware, true)) {
                $config->set($key, [RestrictNovaAccess::class, ...$middleware]);
            }
        }
    }
}
