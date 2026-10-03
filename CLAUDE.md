# CLAUDE.md

@AGENTS.md

## Skills

- Skills in `.claude/skills/`: the architecture skills (`application-layer`, `dependency-injection`, `error-handling`, `validation`, `events`, `testing-architecture`, `domain-layer-cqrs`, `package-boundaries`; see *Architecture* in AGENTS.md), `aegis-security` (read it for any change to who may reach Nova, the whitelist rules, the cache or the commands), `package-upgrades` (anything that reaches an installed application), `package-testing`, `nova-development`, `testing-best-practices`, `laravel-best-practices`.
- Run `make ready` before you say a change is done, and report its result.
- The Aegis core lives in the sibling repository `../nova-aegis`. Never change it from here; a change the module needs from the core is a pull request there first.
- `laravel/nova` is the test double in `stubs/nova`, a copy of the core's: a Nova API the module starts using is added in the core first, then copied here (see `package-testing`).
- Never push to `main`: work on a branch and open a pull request (see *Git workflow* in AGENTS.md). Write everything in English.
