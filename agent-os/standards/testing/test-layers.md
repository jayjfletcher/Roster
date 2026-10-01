# Test Layers

Behavior is tested once, at the action; surfaces test only their own wiring.

| Layer | Location | Invokes via | Covers |
|---|---|---|---|
| Actions | `tests/Feature/*ActionsTest.php` | `app(Action::class)->execute()` | Business rules, edge cases, guards |
| HTTP | `tests/Feature/Http/` | `$this->getJson(route('roster.…'))` | Status codes, validation mapping, JSON paths |
| MCP | `tests/Feature/Mcp/` | `RosterServer::tool(Tool::class, $args)` | Envelopes, errors, HTTP parity |

- Don't duplicate business cases per surface — a delete-in-use rule is one ActionsTest case, not three
- HTTP tests always use `route('roster.…')` names, never literal URLs
- Setup via model factories; fixtures (fake tools, MCP servers, policies) live in `tests/Fixtures`
