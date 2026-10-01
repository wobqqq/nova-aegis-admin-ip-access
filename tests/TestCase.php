<?php

declare(strict_types=1);

namespace Wobqqq\AegisAdminIpAccess\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaCoreServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Wobqqq\Aegis\Aegis;
use Wobqqq\Aegis\AegisServiceProvider;
use Wobqqq\Aegis\Nova\AegisTool;
use Wobqqq\AegisAdminIpAccess\AdminIpAccessModule;
use Wobqqq\AegisAdminIpAccess\AdminIpAccessServiceProvider;
use Wobqqq\AegisAdminIpAccess\Tests\Fixtures\User;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', static function (Blueprint $table): void {
            $table->id();
            $table->string('name')->default('Admin');
            $table->string('email')->unique();
            $table->string('password')->default('');
            $table->boolean('is_admin')->default(false);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Nova::$tools = [];
        Nova::tools([new AegisTool()]);

        Gate::define(AegisTool::GATE, static fn (User $user): bool => $user->is_admin);

        // Outside an HTTP call the suite's request comes from 127.0.0.1 and stands in for the console.
        Aegis::module(new AdminIpAccessModule(static fn (): ?string => request()->ip() === '127.0.0.1' ? null : request()->ip()));
    }

    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            \Inertia\ServiceProvider::class,
            NovaCoreServiceProvider::class,
            AegisServiceProvider::class,
            AdminIpAccessServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('aegis.users.model', User::class);
        $app['config']->set('aegis.audit.schedule', false);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../vendor/wobqqq/nova-aegis/database/migrations');
    }

    /**
     * @param Router $router
     */
    protected function defineRoutes($router): void
    {
        Nova::router()->group(static function (Router $router): void {
            $router->get('/probe', static fn (): string => 'nova page')->name('nova.probe');
        });

        $router->middleware('nova:auth')->get('/nova/sign-in-probe', static fn (): string => 'sign in');
        $router->middleware(['web', 'nova:serving'])->get('/nova/early-probe', static fn (): string => 'registered before the module');
        $router->middleware('web')->get('/welcome', static fn (): string => 'site');
    }
}
