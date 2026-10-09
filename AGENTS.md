# Roster

This repository is a Laravel package. Keep the package focused, idiomatic, and easy for Laravel developers to install, test, and maintain.

## Package Conventions

- Use Laravel-native package APIs and the existing service provider shape before adding abstractions.
- Keep package names, namespaces, Composer metadata, publish tags, documentation, and examples aligned with `jayi/roster`.
- Add only the files and dependencies needed for the package behavior being implemented.
- Prefer explicit Laravel package code over helper abstractions unless the extension point is real.
- Keep tests focused on observable package behavior through public APIs, service provider wiring, commands, routes, published resources, and documentation promises.

## Architecture

- Roster stands on `jayi/foundation`, the suite's shared runtime. `RosterServiceProvider` extends `JayI\Foundation\Support\PackageServiceProvider`, describes the package in `definition()` (key `roster`, `RosterServer`, authorization on by default, history guarded by `roster.audit.view`), calls `registerPackage()` right after merging config, and boots the MCP server, Cortex and the shared history route (`GET {prefix}/history`, `roster.history.index`) through the base helpers. Action events implement `JayI\Foundation\Contracts\ActionStartingEvent` / `ActionFinishedEvent`; finished events wait for the surrounding transaction to commit.
- Code lives in domain modules under `src/Domains/{Domain}` (User, Organization, Team, Invitation, Role, Permission, Impersonation, Sso, Scim, Transfer), mirroring the `mono` domain-module standard. Each domain has a `{Domain}ServiceProvider` (extending `JayI\Foundation\Support\ServiceProvider`) registered by `src/Domains/DomainServiceProvider.php`, its JSON API routes in `routes.php` (loaded with `loadApiRoutesFrom()`), any browser routes in `web.php` (`scim.php` for the SCIM protocol), and only the subdirectories it uses.
- Models are named `{Entity}Model`; their pre-domain class names (`JayI\Roster\Models\{Entity}`) are kept as morph aliases in each domain provider, so stored subject types and other stored values keep the same string. `keen:import-roster` relies on it when it copies the old `roster_audit_entries` into jayi/keen.
- Authorization stays Roster's own: `Http\Request` and `Mcp\Request` extend Foundation's request bases but override `authorize()` to check the request's `ability()` and `scope()` through `Domains\Permission\Services\Authorizer`, not the Gate. MCP tools extend `JayI\Foundation\Mcp\Tool`; `RosterServer` extends `JayI\Foundation\Mcp\Server` and lists `Mcp\Tools\ListRosterHistoryTool`.
- Roster keeps no audit log: jayi/keen records every package's Action events. `Support\Audit` registers what Roster knows on Foundation's `AuditHooks` at boot, whether or not Keen is installed: labels, snapshot extras (profile fields, role permissions, organization domains, assignment slugs), the subject picker and organization scope (Roster's events only), the impersonator/transfer/SCIM token context (every entry) and the password column's redaction. `RosterServiceProvider` names the `scim` and `web` route surfaces on Foundation's `Surface` and defines the `viewAuditLog` Gate ability as global `roster.audit.view` unless the app defines it. The `roster_audit_*` migration stays for `keen:import-roster`. `Roster::audit()` is deprecated and delegates to `Keen::record()`.
- Atrium owns every component and style. Roster ships no stylesheet and no Blade component namespace: views use `x-atrium::*` components and the utilities safelisted in Atrium's stylesheet only, with no `<style>` blocks or `style=` attributes (`tests/Feature/Ui/StylesTest.php` checks with `AtriumStyles`). Shared bits are plain partials (`ui/partials/status-dot`); the impersonation banner is the plain view `roster::impersonation-banner`, Atrium's banner in standalone mode. Record history shows with `<x-atrium::audit-trail source="roster" :subject="$model" />`.
- Cross-domain code stays outside the domains: `Roster`, the facade, `RosterServiceProvider`, `Http\Request`, `Http\Controllers\TrashController`, `Console\Commands\PurgeDeletedCommand`, `Mcp\Request`/`RosterServer`/`Tools\ListRosterHistoryTool` and the `Mcp\Concerns` schema traits, `Support/` (`Users`, `Scopes`, `Slugs`, `UserKey`, `Concerns\ResolvesScopes`), and `Atrium/` (dashboard screens, `ScreenAccess`, `RosterSupportFeature`, and `RedirectDomains`, which offers the MCP redirect domains Cortex keeps on organization and user screens only while Cortex is loaded). `Support\Audit` holds the audit hooks and `Exceptions/` the top-level runtime exceptions.
- The Impex flows (`Transfers\Flows\*`) keep their pre-domain class names: Impex stores them on every run and batch and replays in-flight imports by them.

## Quick Commands

- Full validation: `composer test`
- Formatting check: `composer lint:check`
- Static analysis: `composer analyse`
- Pest tests: `composer test:unit`
- Browser tests: `composer test:browser`
- Workbench build: `composer build`
- Workbench server: `composer serve`

## Local Skills

- `package-scaffold`: use when adding package capabilities or wiring them through the service provider, including commands, migrations, routes, config, views, translations, assets, middleware, publish tags, workbench files, and console-only behavior.
- `package-testing`: use when adding or changing package tests with Pest 4/5 and Orchestra Testbench.
- `package-release`: use when preparing changelog, release notes, tags, or GitHub release workflow changes.
- `package-compatibility`: use when reviewing code, dependencies, or CI against the PHP and Laravel support matrix.
- `package-generate-skill`: use when updating the bundled Boost skill from the package implementation, README, and examples.
