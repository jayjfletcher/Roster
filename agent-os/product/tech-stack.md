# Tech Stack

Follows the same stack as the sibling packages `jayi/atrium`, `jayi/impex`, and `jayi/cortex`.

## Frontend

- Atrium dashboard plugin (`jayi/atrium`) for admin screens
- Blade + Alpine.js 3, Tailwind CSS 4 (Atrium conventions)

## Backend

- PHP ^8.4
- Laravel ^13.15 (`laravel/framework`)
- Laravel MCP (`laravel/mcp`) for MCP tool surface
- Action classes as the single API, exposed via HTTP API, MCP, and Atrium

## Database

- Any Laravel-supported database (SQLite, MySQL, PostgreSQL) via package migrations

## Other

- Testing: Pest 5 (+ Laravel, type-coverage plugins; browser plugin + Playwright where UI tested), Orchestra Testbench 11, workbench app
- Quality: Larastan, Laravel Pint, Laravel PAO
- Ecosystem: `jayi/impex` (import/export, post-launch), `jayi/cortex` (dev integration)
