# Config Documentation

`config/roster.php` is user-facing documentation. Every section gets a banner comment:

```php
/*
|--------------------------------------------------------------------------
| HTTP API Routes
|--------------------------------------------------------------------------
|
| The prefix and middleware applied to the Roster management API routes.
| Add authentication middleware (e.g. auth:sanctum) before exposing
| these routes in production - they manage and execute agents.
|
*/
```

Banner must state:
- What the section controls
- Security implications — anything exposing routes names the auth middleware to add before production (mandatory)
- Available modes/values and what each does (e.g. the `cache` store and fresh/stale windows)

New config key without a documented section = incomplete change.
