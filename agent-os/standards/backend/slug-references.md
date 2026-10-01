# Slug-Based References

Action inputs reference records by slug + integer version number — never by internal id:

```php
'prompt' => ['sometimes', 'nullable', 'string', Rule::exists('roster_prompts', 'slug')],
'prompt_version' => ['sometimes', 'nullable', 'integer', 'min:1'],
'sub_agents.*' => ['string', 'distinct', Rule::exists('roster_agents', 'slug')],
```

- Internal ids stay internal: FK columns (`prompt_id`, `prompt_version_id`) are resolved inside the action — see `Actions/Concerns/ResolvesAgentReferences`
- Why: slugs are stable human/agent-facing identifiers (MCP callers work in slugs), and they survive export/import across environments; ids don't
- Cast resolved keys to string (`(string) $model->getKey()`)
- Adding a cross-record input? Accept slug, validate with `Rule::exists(..., 'slug')`, resolve to FK in the action or a shared concern

## Roster exception: host users

Users belong to the host app and have no slug. Reference them by the user model's route key (`getRouteKeyName()`, usually `id`), validated with `Rule::exists(<users table>, <route key>)`. Roster-owned records (teams, roles, …) still use slugs.
