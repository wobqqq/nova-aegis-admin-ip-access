# Changelog

All notable changes are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [semantic versioning](https://semver.org/).

## [Unreleased]

## [1.0.1] - 2026-10-01

### Changed

- Development and CI run on a test double of Nova (`stubs/nova`, not shipped) and need no Nova license; `make test.nova` runs the PHP suite on the real Nova. Nothing changes for applications.
- The README splits the installation into numbered steps.
- The Dependabot config no longer reads the Nova registry.

## [1.0.0] - 2026-10-01

### Added

- The Admin IP Access section on the Aegis page: open Nova only to listed IPv4 and IPv6 addresses and CIDR subnets, off until enabled.
- The whitelist covers Nova's pages and sign-in, the Nova API and every tool's routes, through Nova's own middleware groups, without editing the application's config files.
- Lock-out protection: the administrator's address is preset, and an enabled list that does not cover the administrator saving it is refused.
- An empty enabled list lets everyone in, and the dashboard warns about it.
- A configurable 403 view, with the module's own page as the fallback, and a JSON answer for the Nova API and the tools.
- The *Nova routes behind Admin IP Access* check, naming Nova routes registered outside Nova's middleware.
- `aegis:admin-ip-access:add-ip` (validated, never twice) and `aegis:admin-ip-access:disable` console commands.

[Unreleased]: https://github.com/wobqqq/nova-aegis-admin-ip-access/compare/v1.0.1...HEAD
[1.0.1]: https://github.com/wobqqq/nova-aegis-admin-ip-access/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/wobqqq/nova-aegis-admin-ip-access/releases/tag/v1.0.0
