---
name: nova-development
description: >-
  Use whenever you change how the module plugs into Laravel Nova and the Aegis
  core: AdminIpAccessServiceProvider (the middleware groups, the config lists,
  the booted() hook), RestrictNovaAccess, NovaRoutesCheck, the module's form
  fields and dashboard line (AdminIpAccessModule), the denied view or the
  translations. Use it with aegis-security for anything about access.
metadata:
  author: project
---

# Nova development (this module)

The module has no Nova tool or Vue code of its own: its settings are a section of the Aegis page, drawn from `fields()`. Check Nova's own source in `vendor/laravel/nova` for version-specific behaviour before relying on it.

## How Nova routes get their middleware

- `NovaCoreServiceProvider::boot()` builds the groups from config: `nova` from `nova.middleware`, `nova:api` from `nova.api_middleware` (which includes `nova`), `nova:asset` from `nova:api`, `nova:auth` from `nova.middleware` plus the guest redirect. `nova:serving` holds `DispatchServingNovaEvent` and `BootTools` and is part of `nova.middleware`.
- Nova's pages are registered with `Nova::router()`, which takes the `nova.middleware` **list** (not the `nova` group); the API under `nova-api` uses `nova:api`; tools use `nova` under `nova-vendor/<tool>`.
- So the module prepends `RestrictNovaAccess` to the four groups (groups resolve at dispatch time, also with cached routes) and to both config lists (for routes registered later), in `$this->app->booted()` so that Nova does not rebuild the groups over it. The request attribute `aegis.admin-ip-access.checked` makes repeated runs free.

## The module in the Aegis page

- `key()` is `admin-ip-access`; `fields()` returns `Field::toggle('enabled')`, `Field::table('ips', [ip, note])` and `Field::text('view')`.
- `defaults()` presets the administrator's own address; `rules()` validates; `status()` is the dashboard line.
- Strings live in `resources/lang/en/admin-ip-access.php` under `aegis-admin-ip-access::admin-ip-access.*`; the view namespace is `aegis-admin-ip-access`.

## Checklist

- [ ] Every Nova route still passes the middleware (`AccessTest`, `RoutesCheckTest`).
- [ ] No business logic in the provider: it wires, the classes decide.
- [ ] New strings in the language file; the denied page prints with `{{ }}` only.
- [ ] `make ready` passes.
