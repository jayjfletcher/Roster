# Route Conventions

All API routes live in `routes/roster.php` inside one group:

```php
Route::prefix($prefix)->middleware($middleware)->name('roster.')->group(...);
```

- Prefix and middleware come from `roster.routes.*` config — never hardcode
- Every route is named under `roster.` (`roster.prompts.versions.publish`)
- Model binding by slug for Roster-owned records (`{team:slug}`); host users bind by their own route key (`{user}`) — see slug-references
- Version segments constrained: `->whereNumber('version')`
- Domain operations (publish, run) are `POST` with a verb suffix on the resource path — never overload PATCH with mode flags:

```php
Route::post('prompts/{prompt:slug}/versions/{version}/publish', ...);
Route::post('agents/{agent:slug}/run', ...);
```

- Explicit route definitions per action — no `Route::resource()`
