# Action Transactions & Return Shape

Every mutating `execute()` wraps its writes in `DB::transaction`:

```php
public function execute(array $data): Prompt
{
    return DB::transaction(function () use ($data): Prompt {
        // all writes here
    });
}
```

- Any write — even a single one — goes inside the transaction. Some older single-write actions skip this; bring them in line when touched, don't copy them.

Mutations return the model with relations explicitly loaded:

```php
return $prompt->load('publishedVersion');
return $agent->refresh()->load(['prompt', 'pinnedVersion', 'subAgents']);
```

- HTTP and MCP serialize the result through the same Resource — relations must be eager-loaded so both surfaces return complete, identical payloads with no lazy-load surprises.
- After updates, `refresh()` first, then `load()`.
