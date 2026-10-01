# AGENTS.md

Guidance for coding agents working in this repository.

## What this is

**Admin IP Access** (`wobqqq/nova-aegis-admin-ip-access`) is a module of [Aegis](https://github.com/wobqqq/nova-aegis), the security suite for Laravel Nova (Laravel 12, PHP 8.2+). It opens Nova (its pages, its sign-in, its API under `nova-api` and every tool under `nova-vendor`) only to the IP addresses and CIDR subnets on a whitelist, and answers any other address with a 403. The rest of the application is not affected.

It is the Nova port of the October CMS module `oc-fortify-admin-ip-access-plugin`, with the fixes made there carried over (see *Lessons from the October version*).

This is a **security product installed on production applications**. A bug here locks every administrator out, or silently leaves Nova open to the internet. Security and safe upgrades come before everything else.

## The self-check gate (run before every commit)

Everything runs in Docker; the host needs no PHP.

```bash
make install        # composer install (the core from ../nova-aegis, Nova from the test double in stubs/nova)
make code.fix       # composer normalize, Rector, PHP CS Fixer
make code.check     # validate --strict, normalize --dry-run, composer audit, php -l, cs, Rector, PHPStan max
make test           # Pest
make test.coverage  # Pest with pcov, failing below 90 %
make ready          # all of the above
make test.nova      # optional: the PHP suite on the real Nova (needs a license)
```

`make ready` must pass. PHPStan runs at `level: max` with strict rules and **no baseline**: fix the type, never add an ignore. Advisories from `composer audit` are fixed by updating the package, never ignored.

The Aegis core is not on Packagist yet: `composer.json` reads it from the path repository `../nova-aegis`, so the core must be checked out next to this repository. `docker-compose.yaml` mounts it read-only at `/work/nova-aegis`. No Nova license is needed: `laravel/nova` resolves to the test double in `stubs/nova` (see *Tests*). `make test.nova` runs the PHP suite on the real Nova and is the only command that needs a license, read from `auth.json` (gitignored and export-ignored). Never read, print or commit it.

## How the code is laid out

| Path | Holds |
|------|-------|
| `src/AdminIpAccessServiceProvider.php` | Wiring only: the module and the check registered with Aegis, the cache clearing on `SettingsSaved`, the middleware added to Nova's groups and config in `booted()`, the commands, the view and translations. |
| `src/AdminIpAccessModule.php` | The `admin-ip-access` section: defaults (the administrator's address preset), validation rules, form fields, the dashboard line. |
| `src/AccessList.php` | The whitelist as the middleware reads it: canonical addresses (keyed for the `isset()` fast path) and subnets, read again from whatever is stored. |
| `src/AccessListStore.php` | The parsed list cached under `aegis.admin-ip-access.v1` and memoized per request (scoped binding). |
| `src/Http/Middleware/RestrictNovaAccess.php` | Runs on every Nova request: allows, or answers 403 with the configured view (HTML) or a JSON message. |
| `src/Rules/` | `IpOrSubnet` (an address or a CIDR subnet) and `CoversCurrentIp` (the lock-out protection). |
| `src/Support/Ip.php` | Parsing, the canonical notation and the "already covered" test. |
| `src/Checks/NovaRoutesCheck.php` | An Aegis check that finds Nova routes registered outside Nova's middleware groups. |
| `src/Console/` | `aegis:admin-ip-access:add-ip` and `aegis:admin-ip-access:disable`, the recovery path. |
| `resources/views/denied.blade.php` | The default 403 page. |
| `resources/lang/en/admin-ip-access.php` | Every label and message, under `aegis-admin-ip-access::admin-ip-access.*`. |
| `stubs/nova/` | The Nova test double the suite and PHPStan run on (export-ignored). |

### How the module uses the core

The core is a separate package that applications update on their own schedule. Use only its public API (listed in the core's AGENTS.md):

- `Aegis::module(new AdminIpAccessModule(...))` registers the section; the core validates it with `rules()`, merges `defaults()`, stores it in `aegis_settings` and draws `fields()` on its settings page.
- `Aegis::settings('admin-ip-access')` reads it, cached by the core. The module never touches the table (an arch test refuses it).
- `Aegis::check(new NovaRoutesCheck(...))` adds a line to the checks; `status()` adds the module's line to the dashboard.
- `Wobqqq\Aegis\Events\SettingsSaved` clears the module's own cache when its section is saved.
- `Aegis::save('admin-ip-access', ...)` is how the commands write, so their values pass the same rules.

A newer core API is used only behind a check (`method_exists`, `class_exists`) with a fallback: the module must keep working with every released core of the same major.

### How the module reaches Nova

`RestrictNovaAccess` is prepended to the `nova`, `nova:api`, `nova:auth` and `nova:serving` middleware groups and to the `nova.middleware` and `nova.api_middleware` config lists at runtime, once every provider has booted. The application's config files are never edited. Groups resolve at dispatch time, so this also holds with cached routes. A request attribute makes the second and later runs free.

## Security rules (always)

Read the `aegis-security` skill for the full checklist. For this module in particular:

- The address is `$request->ip()`. Behind a proxy or a CDN it is the proxy's unless the application configures its trusted proxies. Never read `X-Forwarded-For` or any other header yourself.
- An address is compared with `IpUtils::checkIp()`, never as a string (apart from the canonical `isset()` fast path): subnets and every IPv6 notation must match.
- **Lock-out protection**: the administrator saving an enabled list stays on it (`CoversCurrentIp`), and the form presets their address. A change must never make it possible to save a list that locks out the person saving it.
- An enabled module with an empty list lets everyone in on purpose, so that a mistake never locks every administrator out; the dashboard warns about it.
- **Validate every setting twice**: in `rules()`, and again in `AccessList::fromArray()` where it is used.
- **Escape everything**: the denied page prints with `{{ }}`; validation messages are JSON text.
- **Every request stays cheap**: one cache read, no database query, no logging on an allowed request.
- Cache keys are namespaced and versioned (`aegis.admin-ip-access.v1`); an unreadable cache never breaks a request.
- Never log or print secrets, `auth.json`, a request's headers or cookies.

### Recovery commands

For an administrator who locked themselves out, over SSH:

```bash
php artisan aegis:admin-ip-access:add-ip 203.0.113.7 --note="Home"   # an address or a subnet, validated, never twice
php artisan aegis:admin-ip-access:disable                             # turn the module off, keep the list
```

The console never applies the lock-out rule (there is no administrator's address), and `disable` keeps only the rows the rules accept, so it works even with a broken stored list. Clearing the application cache (`php artisan cache:clear`) is never needed: both commands clear the module's cache.

## Lessons from the October version

Each of these was a bug there; each has a test here:

- The administrator's address was compared as a string: a covering subnet or another IPv6 notation was not recognised by the lock-out rule nor by the preset. Both use `IpUtils` now.
- A saved list stayed cached for an hour and the lock-out rule could be skipped: the cache is cleared on every save of the section, and the rules are the module's own, applied on every save.
- `add-ip` accepted anything and added an address twice: it validates, normalizes and refuses what the list already covers.
- Broken stored values and a cached list of an older shape broke the check: both fall back to safe values and are rebuilt.

## Upgrading installed applications safely

Read the `package-upgrades` skill before changing anything that reaches an application that already runs the module. In short: never rename the section key or the commands; a change to a stored setting keeps reading the old shape; a change to what is cached bumps the cache key's version; defaults stay safe (the module ships off); every change is a line under *Unreleased* in `CHANGELOG.md`.

## Tests

Pest 4 on Orchestra Testbench with the Aegis core (SQLite in memory). No test reaches the network. Read the `package-testing` skill.

`laravel/nova` is the test double in `stubs/nova`: a path repository (`"versions": {"laravel/nova": "5.99.0"}`, symlinked) declared in `composer.json`, so `make install`, CI and PHPStan need no license; the `require` stays `laravel/nova: ^5.0`, and applications get the real Nova because a dependency's repositories are ignored. It is a verbatim copy of the core's `stubs/nova`: never change it here. When the module starts using a Nova API the double lacks, add it to the core's `stubs/nova` first (a pull request there, with the real signature), then copy the directory here; check the change with `make test.nova` when you have a license.

## Git workflow

- `main` is protected: **never push to it and never force-push.** Every change goes through a pull request:
  1. branch off the latest `main`, named after the change (`fix/…`, `feat/…`, `chore/…`, `docs/…`);
  2. commit on the branch and `git push -u origin <branch>`;
  3. open a pull request with the template filled in (what changes, what it means for applications that upgrade);
  4. merge once `make ready` passed, then delete the branch.
- The initial build was the only push to `main`.
- A release is a tag pushed on a merged commit of `main` (`git tag -a v1.0.0 -m "..." && git push origin v1.0.0`); Packagist reads the tag.
- Code, comments, commit messages, pull requests, issues and documentation are written in **English**.

## Conventions

- `declare(strict_types=1);` in every PHP file; PSR-12 via PHP CS Fixer.
- Code documents itself: names over comments. A comment explains a non-obvious *why*, in one sentence.
- Every class is `final`; value objects are `final readonly`.
- Laravel patterns: container bindings, `ValidationRule`, `Cache`, the view factory, Artisan commands.
- Commits: imperative subject saying what the change does for the application ("Refuse a whitelist that locks out its author"), a body with the why.
