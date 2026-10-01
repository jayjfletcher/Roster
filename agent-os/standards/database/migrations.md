# Migration Conventions

Package migrations use a fixed date + sequence number, one file per feature's tables:

```
2026_01_01_000001_create_roster_prompt_tables.php
2026_01_01_000002_create_roster_agent_tables.php
2026_01_01_000003_create_roster_tool_description_tables.php
```

- Fixed `2026_01_01` date + incrementing sequence — package migrations must run in a stable, reviewable order in any host app regardless of authoring time; next migration takes `000004`
- One migration creates all tables for a feature (parent + versions + pivots)
- `ulid('id')->primary()`; FKs via `foreignUlid(...)->constrained(...)->cascadeOnDelete()`
- Compound uniques where the domain demands (`unique(['prompt_id', 'version'])`)
- `down()` drops tables in reverse dependency order
- Anonymous class migrations, `declare(strict_types=1)`
