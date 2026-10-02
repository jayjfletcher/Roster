# Release Notes

## [Unreleased](https://github.com/jayi/roster/compare/v0.1.0...1.x)

### Added

- Users + profiles: user CRUD on the host's own user model (trait, trait-less, or the bundled `JayI\Roster\Models\User`), with configurable key type and column mapping.
- `roster_profiles` table with display name, avatar, timezone, locale, bio and meta.
- Active / suspended / deactivated status with Suspend, Deactivate and Reactivate Actions, plus the `roster.active` middleware.
- JSON API and MCP server for every Action, both disabled by default.
- Atrium plugin: Users screens, a status widget and user search.
- Organizations with teams and members, single owner with transfer, and optional personal organizations.
- Email invitations (optionally onto teams) with a signed, auth-protected accept page; only the invited email can accept.
- Per-organization domain auto-join, for verified emails only.
- Current organization/team context (`SwitchContextAction`, `currentOrganization()`, `currentTeam()`) and the `roster.organization` middleware.
- HTTP, MCP and Atrium surfaces for all of the above.
- Roles and permissions in the database, at global, organization and team scope; shared and organization-specific roles; built-in `super-admin`, `admin`, `member` and `lead` roles.
- Laravel Gate integration (`$user->can()`, `@can`); Atrium's `viewAtrium` gate defaults to the `atrium.view` permission.
- `roster:grant-super-admin` and `roster:sync-permissions` commands; `roster.super_admins` config (verified emails only).
- Authorization on every API route, MCP tool and Atrium screen (`roster.authorization`, on by default), with a privilege-escalation guard on role grants.
- Cortex integration: when `jayi/cortex` is installed, the MCP server and every tool are registered with it, with publishable instruction and description overrides (`roster.cortex`).
- `throttle:roster` rate limiter on the API and MCP (`roster.rate_limit.per_minute`, default 120).
- Missing records on Roster routes answer `404 {"message": "Not found."}` without naming model classes.
- Workbench demo (`composer serve`) and Atrium browser tests (`composer test:browser`).
- Audit log: every Roster change recorded with actor, surface, organization and field diffs (secrets redacted); append-only and hash-chained, with `roster:verify-audit` and `roster:prune-audit`.
- Impersonation: `roster.users.impersonate` permission, one-time signed links usable only by the impersonator, required reason, time limit, `<x-roster::impersonation-banner />`, blocked abilities (`roster.impersonation.blocked`, enforced through the Gate too), locked email/password, and audit entries naming the real actor.
- Single sign-on per organization: OpenID Connect (id_token verified against JWKS), Microsoft Entra ID (`socialiteproviders/microsoft-azure`, single-tenant only, UPN-based), and SAML 2.0 (`socialiteproviders/saml2`). Just-in-time accounts and auto-linking are limited to the organization's domains; optional enforcement through `NotSsoEnforced` / `Roster::ssoRequiredFor()`; encrypted, never-serialized secrets. The protocol packages are optional (`suggest`).
- SCIM 2.0 provisioning per organization (`/scim/v2/{organization}`): Users map to members and Groups to teams; filters, PATCH (Okta and Entra shapes), Bulk, ETags and discovery endpoints. Per-organization bearer tokens are hashed, expirable and revocable. Deprovisioning removes the membership and deactivates only orphaned SCIM-created accounts, never deleting anything. Optional SSO subject linking via `externalId`.
- CSV import and export through `jayi/impex` (optional, `suggest`):
  - Import members, users and teams; export members, users and the audit log.
  - Imports are validated into a per-row preview (create / link / invite / update / skip / error). Nothing changes until confirmed.
  - Confirmed rows are applied as the confirmer, with their permissions re-checked. Errors are reported per row.
  - Unconfirmed imports expire.
  - No password column is accepted, and emails outside the organization's domains become invitations.
  - Exports neutralize spreadsheet formulas and download through an authorized route; MCP gets 15-minute signed links.
  - Files are pruned by `roster:prune-transfers`.
  - Available over HTTP, MCP and Atrium (Imports & exports).
  - Downloadable CSV templates for every import type (header plus `#` comment examples, which imports skip), publishable with the `roster-import-templates` tag. Imports with no rows are refused.
- Organization sync from external systems (ERP, CRM, …):
  - `SyncOrganizationAction` upserts by `source` + `external_id`, writing only the fields sent; `SyncOrganizationsAction` handles bulk, with per-record results.
  - Per-source links (`roster_organization_links`) hold the external id, account number and last sync time. Manage them with `LinkOrganizationAction` / `UnlinkOrganizationAction`, and filter organizations by them.
  - `import_organizations` CSV import type, and an `export_organizations` export in the same columns (optionally one source's records) that imports straight back.
  - New global `roster.organizations.sync` permission.
  - Available over HTTP, MCP and Atrium (Settings tab, list filters).
- Soft deletes:
  - deleted organizations, and users on models using `SoftDeletes` (the bundled model does), go to Deleted with everything kept;
  - a deleted organization is switched off (pages, API, SSO, SCIM, auto-join), with its slug and domains reserved;
  - restore and purge Actions, routes, MCP tools and Atrium screens, with new `roster.users.purge` / `roster.organizations.purge` permissions;
  - `roster:purge-deleted` with `roster.deletes.retention_days` (null keeps forever).
- User approval:
  - a `pending` status with `ApproveUserAction` / `RejectUserAction` (`roster.users.approve`);
  - the starting status is set by `roster.users.registration_status` for self-registration, by a `status` choice when admins create users, and by an organization's `provisioned_status` for its SSO and SCIM accounts;
  - approval emails for approvers and users, each switchable;
  - pending users are blocked by `roster.active` and SSO sign-in.
- Performance:
  - Gate checks without a scope resolve the current organization/team once per request (20 checks: 125 → 9 queries).
  - API user lists and SCIM user pages cost a fixed number of queries whatever their length.
  - The organization page loads and paginates only the open tab.
  - CSV imports cache organization data per run and keep row results in `roster_transfer_rows` (paged with `rows_page`), and exports batch their lookups.
  - New indexes.
- Atrium search: users and organizations are two sources, each with its own result quota and a description for Atrium's classification, and both work under Atrium's process driver (requires Atrium with multi-source plugins).
- SCIM: `eq` filters match in place inside the organization, and Groups pages load their members in fixed queries.
- Fixed: `Authorizer` could keep one request's permissions in long-lived workers (Octane, queues).
- Fixed: concurrent audit writes could fork the hash chain on Postgres; appends now lock a chain-head row.
- Organizations may have no owner (`owner` is optional when creating); personal organizations still require one.
- `Roster::audit()` / `RecordAuditEventAction` for the app's own events (`source: app`), plus `roster.audit.view` / `roster.audit.record` permissions and HTTP, MCP and Atrium surfaces.


## [v0.1.0](https://github.com/jayi/roster/compare/...v0.1.0) - 202x-xx-xx

Initial pre-release.
