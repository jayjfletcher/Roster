# TestCase Environment

All tests extend `RefactorCircus\Roster\Tests\TestCase` (Orchestra Testbench), bound in `Pest.php` via `uses(TestCase::class)->in(__DIR__)`.

Pinned environment — don't undo these:

- `database.default => testing` with `foreign_key_constraints => true` — FK violations must fail in tests
- Providers: `McpServiceProvider`, `AtriumServiceProvider`, then `RosterServiceProvider`
- Package migrations loaded from `database/migrations`, plus the dev dependencies' migrations (`refactor-circus/impex`, `refactor-circus/keen`); workbench `users` table from Testbench's default migrations
- refactor-circus/keen is booted only by `KeenTestCase` (`tests/Modes/Keen`), which covers what Roster teaches the audit log; everywhere else no audit log is installed

Per-test config: call `config()->set(...)` at the start of the test (or a dedicated TestCase subclass) — never mutate the shared `TestCase` for one feature.
