---
name: aegis-security
description: "Security checklist for the Admin IP Access module of Aegis. Use for any change to who may reach Nova: the RestrictNovaAccess middleware and the groups it joins, how the visitor's address is read or compared, the whitelist rules (IpOrSubnet, CoversCurrentIp), AccessList and its cache, the denied page or its JSON answer, the add-ip and disable commands, the routes check, or a security review of this package."
license: MIT
---

# Admin IP Access security checklist

This module decides who can open Nova on production applications. A bug either locks every administrator out or silently opens Nova to the internet. Treat every rule below as a test to write, not a guideline to remember.

## 1. The address

- The visitor's address is `$request->ip()` and nothing else. Behind a load balancer, a proxy or a CDN it is the proxy's until the application configures its trusted proxies; that is the application's job, documented in the README.
- Never read `X-Forwarded-For`, `X-Real-IP`, `CF-Connecting-IP` or any other header yourself: any client can send them. The arch test refuses `getallheaders()`.
- Addresses are compared with `IpUtils::checkIp()`, never as strings, so that subnets and every IPv6 notation of an address match. The only string comparison is the `isset()` fast path on canonical addresses, and a miss there falls through to `IpUtils`.
- A request without a valid address is refused once the list applies (fail closed).

## 2. Lock-out protection

- `CoversCurrentIp` refuses an enabled, non-empty list that does not cover the administrator saving it. It must keep working for subnets and other IPv6 notations.
- The form presets the administrator's address (`defaults()`) and names it in the help text.
- An enabled module with an empty list lets everyone in on purpose, and the dashboard says so (`status()` warns).
- The console never sees an administrator's address (`runningInConsole()`), so `add-ip` and `disable` always work. They are the recovery path: never make them depend on a valid stored list (`disable` saves only the rows `AccessList::rows()` accepts).
- A new option that can lock someone out ships off, and comes with its own way back from the console.

## 3. Coverage of Nova

- `RestrictNovaAccess` is prepended to the `nova`, `nova:api`, `nova:auth` and `nova:serving` groups and to `nova.middleware` / `nova.api_middleware`, in `$app->booted()`, after Nova built them. Never ask the application to edit its config files.
- Every route under the Nova path, `nova-api/` and `nova-vendor/` must pass it. `NovaRoutesCheck` reports the ones that do not; a test pins each kind of route (page, sign-in, API, tool, a route registered with `nova:serving` only).
- The rest of the application is never affected.

## 4. Input: validate twice

- `AdminIpAccessModule::rules()` bounds every value: `boolean`, `array|max:100`, `array:ip,note` (no unknown columns), `max:100` strings, `IpOrSubnet`, a strict view-name regex (no `/`, no `..`).
- `AccessList::fromArray()` reads the stored values again with `Ip::normalize()` and `Values`, skipping what the rules would refuse: the stored row may predate the rules or be written by hand.
- The configured view is used only if it matches the pattern and exists; anything else, or a view that throws while rendering, falls back to `aegis-admin-ip-access::denied`.

## 5. Output: escape everything

- The denied page prints with `{{ }}` only. The visitor's address is printed only after `FILTER_VALIDATE_IP`.
- Validation messages that repeat a submitted value are JSON text, drawn by the Aegis page with `{{ }}`; never mark them up.
- JSON requests (the Nova API, the tools) get `{"message": ...}` with a 403, never HTML.
- The 403 carries `Cache-Control: no-store, private`, so a shared cache never serves it to an allowed address.

## 6. Every request stays cheap

- A Nova request costs one cache read: `AccessListStore` caches the parsed list as an array under `aegis.admin-ip-access.v{N}` and memoizes it for the request (scoped binding, so long-running workers start fresh).
- No database query, no view lookup and no logging on an allowed request.
- The cache is cleared on `SettingsSaved` for this section. An unreadable cache falls back to `Aegis::settings()`; it never turns a request into a 500.

## 7. Secrets and data

- Never log a request's headers, cookies or the whitelist. A failing custom view is reported with `report()`, which logs the exception, not the request.
- Never print or commit `auth.json`.

## Review procedure

1. `git diff --stat` and list every changed rule, route group, cache key, view and command.
2. Walk each through sections 1 to 6 and name the test that pins it.
3. Run `make ready`.
4. Report each finding as: file:line, what an attacker sends, what happens, the fix.
