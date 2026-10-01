# Changelog

All notable changes are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [semantic versioning](https://semver.org/).

## [Unreleased]

### Added

- The Admin IP Access section on the Aegis page: open Nova only to listed IPv4 and IPv6 addresses and CIDR subnets, off until enabled.
- The whitelist covers Nova's pages and sign-in, the Nova API and every tool's routes, through Nova's own middleware groups, without editing the application's config files.
- Lock-out protection: the administrator's address is preset, and an enabled list that does not cover the administrator saving it is refused.
- An empty enabled list lets everyone in, and the dashboard warns about it.
- A configurable 403 view, with the module's own page as the fallback, and a JSON answer for the Nova API and the tools.
- The *Nova routes behind Admin IP Access* check, naming Nova routes registered outside Nova's middleware.
- `aegis:admin-ip-access:add-ip` (validated, never twice) and `aegis:admin-ip-access:disable` console commands.
