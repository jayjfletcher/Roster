---
name: roster-development
description: >
  Configure and apply the Roster package in Laravel applications: user CRUD,
  profiles, suspend/deactivate/reactivate, organizations, teams, invitations,
  domain auto-join, current org/team context, roles and permissions through
  Laravel's Gate, audit log, impersonation, SSO, SCIM, CSV import/export,
  the roster.active and roster.organization middleware, and Roster's HTTP
  API, MCP server and Atrium screens.
license: MIT
metadata:
  author: Jay Fletcher
---

# Roster

Use this skill when a Laravel application manages users, profiles, account status, organizations/teams (tenancy), memberships or invitations with the `jayi/roster` package.

## Primary Goal

- Use the `jayi/roster` package's public API in the smallest correct way. Every operation is an Action in `JayI\Roster\Actions`; call the Action instead of writing the queries yourself.

## Workflow

### 1. Inspect the Laravel app context

- Confirm `jayi/roster` is in `composer.json` and find the user model (`config('roster.users.model')`, default `App\Models\User`).
- Check the user model's primary key type and column names.

### 2. Install and configure

```bash
php artisan vendor:publish --tag="roster-config"
php artisan vendor:publish --tag="roster-migrations"
php artisan migrate
```

- Set `roster.users.key_type` (`int` | `ulid` | `uuid`) **before** migrating; it decides the type of the `roster_profiles.user_id` column.
- If the users table uses other column names, set `roster.users.columns` (`name`, `email`, `password`; set `name` to `null` if there is none).
- Pick a user mode:
  - Add `use JayI\Roster\Concerns\HasRoster;` to the user model (recommended). It provides `roster()`, `rosterStatus()`, `isRosterActive()` and the `rosterProfile` relation.
  - Or leave the model untouched. Roster registers `rosterProfile` dynamically; use `app(JayI\Roster\Support\Users::class)->profile($user)` / `->status($user)`.
  - Or, with no users table, use `JayI\Roster\Models\User` and `php artisan vendor:publish --tag="roster-users-migration"`.

### 3. Apply the Actions

- Resolve with `app(Action::class)->execute(...)`. Validate input with `Action::rules()`; `UpdateUserAction::rules($user)` takes the user so its own email passes the unique check.
- Pass the acting user as `actor`. Status changes and deletes then refuse to act on yourself.
- Business-rule failures throw `ValidationException`; let Laravel render them.

### 4. Organizations, teams and invitations

- Create organizations with `CreateOrganizationAction` (`owner` is a user route key, or pass `owner:` a model). The owner becomes a member; only `TransferOwnershipAction` changes the owner.
- Add members with `AddMemberAction`, then seat them on teams with `AddTeamMemberAction`; non-members are rejected.
- Invite with `CreateInvitationAction($organization, ['email' => ..., 'teams' => [slugs]], actor: $user)`. The email links to Roster's signed page (behind `roster.invitations.middleware`); only a signed-in user with the invited email can accept. Use `roster.invitations.accept_url` for a custom page that calls `AcceptInvitationAction`.
- Domain auto-join: set an organization's `domains` and `auto_join`. It fires only for verified emails on models implementing `MustVerifyEmail`.
- Read context with `$user->currentOrganization()` / `currentTeam()` (or `app(JayI\Roster\Roster::class)`); change it with `SwitchContextAction`.

### 5. Roles and permissions

- Declare app permissions with `CreatePermissionAction` (`['name' => 'invoices.edit']`), bundle them with `CreateRoleAction` (`scope`: global | organization | team; `organization` for an organization's own role), and assign with `AssignRoleAction($user, ['role' => $id, 'organization' => slug, 'team' => slug])`.
- Check with Laravel's Gate: `$user->can('invoices.edit', $organization)`, `@can`, `Gate::allows()`. Without an organization or team argument, the user's current context is used.
- Bootstrap: `php artisan roster:grant-super-admin {email}`. Run `php artisan roster:sync-permissions` after upgrading.
- Roster's own API, MCP tools and Atrium screens require the `roster.*` permissions (`roster.authorization`, on by default).
- With `jayi/cortex` installed, every Roster tool is available to Cortex agents automatically. Agents act as the signed-in user; limit them with `roster.cortex.tools`.
- The API and MCP are throttled by `throttle:roster` (`roster.rate_limit.per_minute`). Keep it in custom `middleware` lists.

### 6. Audit log

- Roster records every change automatically. Read it via `ListAuditEntriesAction`, `GET /roster/audit` or Atrium; access needs `roster.audit.view` (global, per organization, or about yourself).
- Record the app's own events with `Roster::audit('invoice.paid')->on($model)->in($organization)->with([...])->changes([...])->record();` (facade `JayI\Roster\Facades\Roster`). Action names are dot-separated lower case.
- Add app secrets to `roster.audit.redact`. Schedule `roster:prune-audit`; run `roster:verify-audit` to check for tampering.

### 7. Single sign-on

- Install the protocol packages you need: `laravel/socialite` plus `firebase/php-jwt` (OIDC), `socialiteproviders/microsoft-azure` (Entra ID) or `socialiteproviders/saml2` (SAML).
- Create connections per organization with `CreateSsoConnectionAction($organization, ['name' => ..., 'protocol' => 'oidc'|'azure'|'saml', ...settings])`, and register the returned `callback_url` at the provider.
- Link people to `route('roster.sso.discover', ['email' => $email])` or `route('roster.sso.start', $connection->slug)`.
- When an organization enforces SSO, add `new \JayI\Roster\Rules\NotSsoEnforced` to the app's password login validation.

### 8. SCIM provisioning

- Issue a token with `CreateScimTokenAction($organization, ['name' => 'Okta', 'sso_connection' => $slug?])`; `$issued->plain` is the only copy. The provider's base URL is `url('scim/v2/'.$organization->slug)`.
- The organization must own its email domains (`domains`), or SCIM refuses its users.
- SCIM changes run through Roster's Actions: listen to the normal Action events, and find them in the audit log under surface `scim`.

### 9. Impersonation

- Grant `roster.users.impersonate` explicitly. Start with `StartImpersonationAction($user, ['reason' => ...], actor: $admin)` and redirect the impersonator's browser to `$started->url`.
- Add `<x-roster::impersonation-banner />` to the app layout. List app abilities that must never run while impersonating in `roster.impersonation.blocked` (wildcards allowed).
- Use `app(JayI\Roster\Impersonation\ImpersonationContext::class)->active()` to detect an impersonated request.

### 10. CSV import and export

- Requires `jayi/impex`: `composer require jayi/impex`, `php artisan vendor:publish --tag="impex-migrations"`, migrate, and run a queue worker and the scheduler. Schedule `roster:prune-transfers`.
- Start with `StartImportAction(['type' => 'import_members'|'import_users'|'import_teams', 'organization' => slug, 'file' => $upload /* or 'content' => $csv */], $user)`. It only previews: read `$transfer->rows()` (`action` is create/link/invite/update/skip/error).
- Apply with `ConfirmImportAction($transfer, $user)`. Rows run as that user, with their permissions checked again. Cancel with `CancelTransferAction`.
- Export with `StartExportAction(['type' => 'export_members'|'export_users'|'export_audit', 'organization' => slug, 'filters' => [...]], $user)`, then serve the file through `route('roster.transfers.download', $transfer->id)` or Atrium.
- Columns: members `email,name,display_name,teams,role`; users `email,name,display_name`; teams `name,slug,members`.
- Without Impex, these Actions throw `JayI\Roster\Transfers\TransfersUnavailableException`.

### 11. Guard routes and surfaces

- Add `roster.active` middleware to routes only active users may use, and `roster.organization` to routes that need a tenant.
- The HTTP API (`roster.routes.enabled`) and MCP (`roster.mcp.web.enabled`, `roster.mcp.local.enabled`) are off by default and do **no authorization** themselves. Only enable them with auth plus admin-only middleware in the `middleware` config.

## Rules, References, and Templates

- Config: `config/roster.php` (users, authorization, super_admins, roles, audit, impersonation, sso, scim, transfers, organizations, invitations, routes, mcp).
- Statuses: `JayI\Roster\Enums\UserStatus` (`Active`, `Suspended`, `Deactivated`). A user with no profile row is `Active`.
- Events: `JayI\Roster\Events\Action\*ActionEvent`; listen to `JayI\Roster\Contracts\ActionStartingEvent` / `ActionFinishedEvent` to see every action.
- HTTP routes are named `roster.users.*`, `roster.organizations.*`, `roster.invitations.*`; `{user}` is the user model's route key, organizations and teams use slugs.
- Atrium: Users at `atrium.roster.users.index`, Organizations at `atrium.roster.organizations.index`. Access uses Atrium's `viewAtrium` gate. Hide it with `atrium.disabled => ['roster']`.

## Examples

```php
use JayI\Roster\Actions\CreateUserAction;
use JayI\Roster\Actions\SuspendUserAction;
use JayI\Roster\Actions\UpdateProfileAction;

$user = app(CreateUserAction::class)->execute([
    'name' => 'Ada Lovelace',
    'email' => 'ada@example.com',
    'timezone' => 'Europe/London', // profile fields are accepted on create
]);

app(UpdateProfileAction::class)->execute($user, ['display_name' => 'Ada']);

app(SuspendUserAction::class)->execute($user, ['reason' => 'Chargeback'], actor: $request->user());

$user->isRosterActive(); // false
```

```php
use JayI\Roster\Actions\CreateInvitationAction;
use JayI\Roster\Actions\CreateOrganizationAction;

$acme = app(CreateOrganizationAction::class)->execute(['name' => 'Acme', 'domains' => ['acme.com']], owner: $request->user());

app(CreateInvitationAction::class)->execute($acme, ['email' => 'ada@example.com'], actor: $request->user());

$request->user()->currentOrganization()?->slug; // 'acme'
```

```php
Route::middleware(['auth', 'roster.active', 'roster.organization'])->group(function () {
    // only active users who belong to an organization
});
```

In app tests, assert with `expect($user->rosterStatus())->toBe(UserStatus::Suspended)`, `Event::fake([UserSuspendedActionEvent::class])`, or `Notification::fake()` + `Notification::assertSentOnDemand(JayI\Roster\Notifications\InvitationNotification::class)`.

## Anti-patterns

- Adding profile or status columns to the users table: Roster keeps them in `roster_profiles`.
- Writing `roster_profiles` rows or status fields directly instead of calling the Actions, which skips the guards and events.
- Enabling `roster.routes` or `roster.mcp.web` without authentication middleware; permission checks need a signed-in user.
- Setting `roster.authorization` to false in production.
- Running Cortex agents with no signed-in user and expecting Roster tools to work; they are refused.
- Updating or deleting `roster_audit_entries` rows: the model refuses, and raw SQL edits are caught by `roster:verify-audit`.
- Putting secrets in `Roster::audit()->with()` without listing their keys in `roster.audit.redact`.
- Logging users in as someone else with `Auth::login()` for support. Use impersonation, which is time-limited, blocked from sensitive abilities, and audited.
- Emailing or posting impersonation links. They only work in the impersonator's own browser.
- Using multi-tenant Entra authorities (`common`, `organizations`) or the Entra `mail` attribute for identity. Roster refuses both.
- Writing your own OIDC/SAML verification. Use Roster's connections, which verify tokens and assertions with proven libraries.
- Storing or logging SCIM tokens. They are shown once; issue a new one and revoke the old if lost.
- Checking roles by slug in app code (`hasRole('admin')`-style). Check permissions with `$user->can()` instead.
- Writing `roster_role_assignments` rows directly, which skips the scope and escalation guards.
- Changing `roster.users.key_type` after `roster_profiles` has been migrated.
- Putting a `password` column in an import CSV (it is refused), or serving `roster.transfers.disk` files publicly instead of through Roster's download route.
- Calling a user model's `profile()` relation for Roster data: the relation is `rosterProfile` (and `rosterMemberships` / `rosterOrganizations`).
- Writing `roster_memberships` or `roster_team_members` rows directly; seats must go through an organization membership.
- Removing `auth` from `roster.invitations.middleware`: accepting acts as the signed-in user.
- Enabling domain auto-join and expecting unverified users to join; that is deliberately blocked.
