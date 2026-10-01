# Container Bindings

Binding lifetime in `RosterServiceProvider::register()` is a deliberate choice per service:

```php
$this->app->singleton(Support\Users::class); // config-derived state
$this->app->singleton(Roster::class);
```

- `singleton` for services holding code/config-level state — safe for the process lifetime
- `scoped` for services that memoize database state — a singleton would serve stale data across requests in long-running workers (Octane, queues)
- Rule when adding a binding: memoizes DB/request state → `scoped`; pure code/config state → `singleton`
- Actions are never bound — resolved fresh via `app(Action::class)` each call
