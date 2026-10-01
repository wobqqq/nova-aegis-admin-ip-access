---
name: package-testing
description: "How Admin IP Access is tested. Use when writing or changing anything in tests/ (Pest files, tests/TestCase.php, tests/Pest.php, tests/Fixtures), phpunit.xml.dist, when a test needs Nova, the Aegis core, an administrator's address, the console or the cache, when a test fails only in the suite, or when PHPStan complains about a test."
license: MIT
---

# Testing the module

## The harness

- Pest 4 on Orchestra Testbench (Laravel 12) with the real `laravel/nova` from nova.laravel.com and the Aegis core from the `../nova-aegis` path repository. SQLite in memory, array cache and session (`phpunit.xml.dist`).
- `tests/TestCase.php` loads `Inertia\ServiceProvider`, `NovaCoreServiceProvider`, `AegisServiceProvider` and `AdminIpAccessServiceProvider`, runs the core's migrations, creates the `users` table for `tests/Fixtures/User.php`, registers `AegisTool` and defines `viewAegis` as `is_admin`.
- It registers probe routes: a Nova page (`/nova/probe`, through `Nova::router()`), a sign-in route (`nova:auth`), a route registered with `nova:serving` only (`/nova/early-probe`) and a site route outside Nova (`/welcome`). Nova's API (`/nova-api/*`) and the core's tool routes (`/nova-vendor/aegis/*`) are real.
- The suite runs in the console, so `TestCase` registers the module with an address resolver that treats the default `127.0.0.1` request as the console and any test request as an administrator's.
- `tests/Pest.php` helpers: `whitelist()` saves the section as the console does, `visitFrom($uri, $ip)` calls a route from an address, `asAdminFrom($ip, $method, $uri, $data)` calls the Aegis API as a signed-in administrator from an address. `Fixtures\Addresses` holds the documentation addresses the tests use.

## Rules

- Test what an administrator or a visitor sees: the status code and page a given address gets, the JSON the Aegis API answers, the dashboard line, the exit code and output of a command. Not private methods.
- A security rule is a test: an address outside the list, the lock-out refusal, an invalid entry, a missing or broken view, a cache of another shape, a down cache store, a route outside Nova's groups.
- Use documentation addresses only (`192.0.2.0/24`, `198.51.100.0/24`, `203.0.113.0/24`, `2001:db8::/32`).
- A setting is saved through `Aegis::save()` (or the API), never written to the table by hand, unless the test is about a stored row the rules would refuse.
- No test reaches the network.
- Coverage stays at 90 % or more (`make test.coverage`).

## PHPStan max on tests, without ignores

- Use the global `Pest\Laravel\*` functions (`getJson`, `withServerVariables`, `actingAs`), never `$this->` in a closure.
- Console: `expect(Artisan::call('aegis:admin-ip-access:disable'))->toBe(0)` and `Artisan::output()`.
- Annotate mocks (`/** @var CacheRepository&MockInterface $cache */`) and dataset arrays (`/** @var array<string, mixed> $values */`).
- Read `mixed` values with `data_get()` or narrow them with `is_array()` before indexing.
- Constants used across files live in a class (`Fixtures\Addresses`): PHPStan does not see constants declared in `tests/Pest.php`.

## Workflow

1. Write the change and its tests; iterate with `docker compose run --rm php vendor/bin/pest --filter='...'`.
2. `make composer.test.coverage` for gaps; cover the uncovered decisions, not getters.
3. `make ready` before the commit.
