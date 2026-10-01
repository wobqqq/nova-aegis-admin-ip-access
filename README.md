# Aegis Admin IP Access

[![Packagist](https://img.shields.io/packagist/v/wobqqq/nova-aegis-admin-ip-access)](https://packagist.org/packages/wobqqq/nova-aegis-admin-ip-access)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777bb4)](https://github.com/wobqqq/nova-aegis-admin-ip-access/blob/main/composer.json)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen)](https://github.com/wobqqq/nova-aegis-admin-ip-access/blob/main/phpstan.neon.dist)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://github.com/wobqqq/nova-aegis-admin-ip-access/blob/main/LICENSE.md)

**Admin IP Access** is a module of [Aegis](https://github.com/wobqqq/nova-aegis), the security suite for Laravel Nova. It opens Nova only to the IP addresses and subnets you list, and answers every other address with a 403, before a login form, an API endpoint or a tool is ever reached.

## 🚀 Features

- **The whole of Nova**: its pages and sign-in, the Nova API (`/nova-api/*`) and the routes of every tool (`/nova-vendor/*`). The rest of the application is not affected.
- **IPv4 and IPv6**, single addresses and CIDR subnets (`203.0.113.7`, `10.0.0.0/8`, `2001:db8::/32`), matched in any notation.
- **Lock-out protection**: your own address is preset in the list, and a list that would not let you in is refused when you save it.
- **Safe when empty**: an enabled module with an empty list lets everyone in, so a mistake never locks every administrator out, and the Aegis dashboard warns about it.
- **Your own 403 page**: name any Blade view; a missing or broken one falls back to the module's page. The Nova API and the tools get a JSON answer.
- **Recovery from the console**: add an address or turn the module off over SSH.
- **Cheap on every request**: one cache read, no database query.
- **A routes check** on the Aegis page that names any Nova route registered outside Nova's middleware, so it cannot skip the whitelist unnoticed.
- No change to your config files: the module joins Nova's middleware on its own.

## 📦 Requirements

- PHP 8.2 or higher
- Laravel 12
- Laravel Nova 5
- [Aegis](https://github.com/wobqqq/nova-aegis), installed with the module

## 📥 Installation

```bash
composer require wobqqq/nova-aegis-admin-ip-access
php artisan migrate
```

The service provider is discovered automatically. `php artisan migrate` creates the Aegis settings table if the core is new to the application; the module has no migration of its own.

Then open **Aegis → Settings → Admin IP Access** in Nova:

1. check the list: your current address is already in it;
2. add the other addresses or subnets that need Nova (an office, a VPN, a deployment host), with a note for each;
3. switch on **Restrict Nova to the listed addresses** and save.

## ⚙️ Configuration

Everything is set on the Aegis page; there is no config file to publish.

| Setting | Default | What it does |
|---|---|---|
| Restrict Nova to the listed addresses | off | Turns the whitelist on. |
| Allowed addresses | your address | Up to 100 addresses or CIDR subnets, each with an optional note. |
| Denied page view | `aegis-admin-ip-access::denied` | The Blade view answered with the 403. It receives `$ip`, the visitor's address. |

To change the module's own page, publish it:

```bash
php artisan vendor:publish --tag=aegis-admin-ip-access-views
```

The module uses the cache store Aegis uses (`AEGIS_CACHE_STORE`, the default store otherwise). Use a store all your servers share (Redis, Memcached, the database), so a change made on one server or from the console applies everywhere at once.

## 🆘 Recovery commands

Locked out because your address changed? Over SSH, on the server:

```bash
php artisan aegis:admin-ip-access:add-ip 203.0.113.7               # add an address
php artisan aegis:admin-ip-access:add-ip 192.0.2.0/24 --note="VPN" # or a subnet, with a note
php artisan aegis:admin-ip-access:disable                          # or turn the module off, keeping the list
```

`add-ip` refuses anything that is not an address or a subnet and does not add one the list already covers.

## ⚠️ Good to know

- **Proxies, load balancers and CDNs.** The module checks `$request->ip()`. Behind a proxy that is the proxy's address, unless you tell Laravel which proxies to trust (`$middleware->trustProxies(at: [...])` in `bootstrap/app.php`). Then Laravel reads the visitor's address from the forwarded headers the trusted proxy sets.
- **Forwarded headers can be forged.** `X-Forwarded-For` is just a header any client can send. Trust only the proxies you run, by address, and make sure the application cannot be reached around them. `trustProxies(at: '*')` is safe only if nothing but your proxy can reach the server.
- **Whitelist static addresses.** A home connection or a mobile network usually changes its address; prefer an office, a VPN or a bastion host, and keep SSH access for the recovery commands.
- **Several servers** need a shared cache store (see *Configuration*), or a change waits for each server's cache to expire (up to an hour).
- **Nova on its own domain** is covered the same way: the module follows Nova's middleware, not a URL prefix.
- **A tool that registers its routes without Nova's middleware** is not covered. The *Nova routes behind Admin IP Access* check on the Aegis page names such routes.

## ⬆️ Upgrading

See [CHANGELOG.md](https://github.com/wobqqq/nova-aegis-admin-ip-access/blob/main/CHANGELOG.md).

## 🔒 Security

Please report a vulnerability privately, as described in [SECURITY.md](https://github.com/wobqqq/nova-aegis-admin-ip-access/blob/main/SECURITY.md).

## 🛠️ Development

The toolchain runs in Docker, the host needs nothing but `docker` and `make`. Until the core is on Packagist, Composer installs it from a checkout of [nova-aegis](https://github.com/wobqqq/nova-aegis) next to this repository (`../nova-aegis`), which Docker mounts read-only. Nova is a licensed package, so installing the development dependencies needs your own Nova license: put its credentials in `auth.json` (gitignored) or run `composer config http-basic.nova.laravel.com <email> <license-key>`.

```bash
make install        # composer install
make code.fix       # composer normalize, Rector, PHP CS Fixer
make code.check     # composer validate/audit, php -l, PHP CS Fixer, Rector, PHPStan (level max)
make test.coverage  # Pest with coverage (90 % minimum)
make ready          # everything above
```

## 📄 License

MIT, see [LICENSE.md](https://github.com/wobqqq/nova-aegis-admin-ip-access/blob/main/LICENSE.md).
