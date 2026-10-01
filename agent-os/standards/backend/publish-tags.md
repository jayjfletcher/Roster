# Publish Tags

Every publishable group carries the umbrella tag plus its own specific tag:

```php
$this->publishes([
    __DIR__.'/../config/roster.php' => config_path('roster.php'),
], ['roster', 'roster-config']);
```

- Tags: `roster` (everything) + `roster-config`, `roster-views`, `roster-lang`, `roster-migrations`
- Migrations use `publishesMigrations()` (re-dates files on publish)
- All `publishes()` + `commands()` registrations sit behind the `runningInConsole()` guard — keep new ones there
- New publishable group = same dual-tag shape, named `roster-<thing>`
