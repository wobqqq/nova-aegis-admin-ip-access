---
name: package-upgrades
description: "How a change reaches the Laravel applications that already run Admin IP Access. Use before changing a stored setting of the admin-ip-access section (its key, type or meaning), a default value, the cache key or the shape of what is cached, the commands' names, the view or translation namespaces, the middleware groups the module joins, the composer.json constraints (including the Aegis core), or when preparing a release or a tag."
license: MIT
---

# Upgrading installed applications

Applications update the Aegis core and this module independently with Composer. Every change is written for an application that has been running the previous version for months, possibly with an older or a newer core.

## Versions and releases

- Semantic versions: a fix is a patch, a new option a minor, a removed option, a renamed command or a changed default that can lock someone out a major.
- Every change adds a line under *Unreleased* in `CHANGELOG.md` saying what changes for the developer. A release moves them under the version and date.
- Release: merge the pull request, then `git tag -a v1.0.1 -m "..." && git push origin v1.0.1`. Packagist reads the tag.

## Constraints

- `laravel/nova` stays `^5.0` and `laravel/framework` `^12.0`: whole majors. Supporting a new major is a minor release with both ranges and tests against both.
- `wobqqq/nova-aegis` is `^1.1 || dev-main` (1.1 added `Aegis::save()`). Until the core is on Packagist it comes from the `../nova-aegis` path repository; once it is, drop the path repository and `dev-main` in one pull request.
- The lock file is for development only (export-ignored); the ranges are what applications resolve.

## The core's contract

- Use only the core's public API: `Aegis::module()`, `::check()`, `::settings()`, `::save()`, `Contracts\Module` and `Check`, `CheckResult`, `Field`, `Status`, `SettingsSaved`, `Support\Values`, the `aegis.cache_store` config key.
- A newer core API is used only behind a check (`method_exists`, `class_exists`) with a fallback, so the module keeps working on every released core of the same major.
- Never read or write the `aegis_settings` table directly; the arch test refuses it.

## Stored settings

The section is one `aegis_settings` row under the key `admin-ip-access`: `enabled`, `ips` (rows of `ip` and `note`), `view`.

- The core merges the stored values over `defaults()` and drops keys the defaults no longer name, so adding a setting needs no migration.
- Changing a setting's type or meaning: prefer a **new key**. `AccessList::fromArray()` keeps reading the old shape and skips what it cannot read.
- Never rename the section key: applications would lose their whitelist and Nova would open to everyone.

## Cached values

- `AccessListStore` caches arrays under `aegis.admin-ip-access.v1`. A change to the shape of the cached array bumps the version (`CACHE_KEY`).
- `AccessList::fromCache()` treats any other shape as a miss; keep reading through it.

## Defaults

- A new protection ships disabled, or with a default that cannot block the current administrator.
- Changing a default changes the behaviour of every application that never saved the section: say so in the changelog, or keep the old default.
- The command names (`aegis:admin-ip-access:add-ip`, `:disable`) are documented recovery commands: never rename them in a minor release.
