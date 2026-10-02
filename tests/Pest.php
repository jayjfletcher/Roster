<?php

declare(strict_types=1);

use JayI\Roster\Access\Permissions;
use JayI\Roster\Actions\CreateOrganizationAction;
use JayI\Roster\Mcp\RosterServer;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Permission;
use JayI\Roster\Models\Role;
use JayI\Roster\Models\RoleAssignment;
use JayI\Roster\Models\Team;
use JayI\Roster\Tests\AuditOffTestCase;
use JayI\Roster\Tests\AuthorizationTestCase;
use JayI\Roster\Tests\BrowserTestCase;
use JayI\Roster\Tests\CortexTestCase;
use JayI\Roster\Tests\DefaultsTestCase;
use JayI\Roster\Tests\SsoUnavailableTestCase;
use JayI\Roster\Tests\TestCase;
use JayI\Roster\Tests\TraitlessTestCase;
use JayI\Roster\Tests\TransfersUnavailableTestCase;
use JayI\Roster\Tests\UlidTestCase;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Testing\TestResponse;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use Laravel\Mcp\Transport\JsonRpcResponse;
use Workbench\App\Models\User;
use Workbench\Database\Factories\UserFactory;

// pest-plugin-browser removes tests/Browser/Traces with @rmdir() when it
// boots; on a missing directory that raises a (suppressed) warning, which
// failOnWarning turns into a failed run. An empty directory removes cleanly.
if (! is_dir(__DIR__.'/Browser/Traces')) {
    @mkdir(__DIR__.'/Browser/Traces');
}

uses(TestCase::class)->in('Feature');
uses(AuthorizationTestCase::class)->in('Authorization');
uses(CortexTestCase::class)->in('Cortex');
uses(BrowserTestCase::class)->in('Browser');
uses(DefaultsTestCase::class)->in('Defaults');
uses(TraitlessTestCase::class)->in('Modes/Traitless');
uses(UlidTestCase::class)->in('Modes/Ulid');
uses(TestCase::class)->in('Modes/Owned');
uses(AuditOffTestCase::class)->in('Modes/AuditOff');
uses(SsoUnavailableTestCase::class)->in('Modes/SsoUnavailable');
uses(TransfersUnavailableTestCase::class)->in('Modes/TransfersUnavailable');

/**
 * Call a Roster tool the way a client reaches it.
 *
 * Every tool sits behind ToolSearch, so it is reachable only through the
 * execute_tools entry point. This routes the call through that entry point
 * and lifts the inner tool's response back to the top level so the usual
 * assertions apply to the tool that actually ran.
 *
 * @param  class-string<Tool>  $tool
 * @param  array<string, mixed>  $arguments
 */
function mcpTool(string $tool, array $arguments = []): TestResponse
{
    $entryPoints = (new RosterServer(new FakeTransporter))->createContext()->tools();

    $execute = $entryPoints->firstOrFail(
        fn (Tool $candidate): bool => $candidate->name() === 'execute_tools',
    );

    $primitive = app($tool);

    $envelope = $execute->handle(new Request([
        'calls' => [['name' => $primitive->name(), 'arguments' => $arguments]],
    ]));

    /** @var array<string, mixed> $decoded */
    $decoded = json_decode(
        (string) collect($envelope)->firstOrFail()->content(),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    /** @var array<string, mixed> $result */
    $result = $decoded['results'][0] ?? [];

    return new TestResponse($primitive, JsonRpcResponse::result(1, array_filter([
        'content' => $result['content'] ?? [],
        'structuredContent' => $result['structuredContent'] ?? null,
        'isError' => $result['isError'] ?? false,
    ], fn (mixed $value): bool => $value !== null)));
}

function user(array $attributes = []): User
{
    return UserFactory::new()->create($attributes);
}

/**
 * Give a user a role by slug: global, in an organization, or on a team.
 */
function grant(User $user, string $slug, ?Organization $organization = null, ?Team $team = null): void
{
    $role = Role::query()->where('slug', $slug)->firstOrFail();

    RoleAssignment::query()->create([
        'role_id' => $role->getKey(),
        'user_id' => $user->getKey(),
        'organization_id' => $organization?->getKey() ?? $team?->organization_id,
        'team_id' => $team?->getKey(),
    ]);

    app(Permissions::class)->flush();
}

/**
 * A global role holding exactly the given permissions.
 *
 * @param  array<int, string>  $permissions
 */
function roleWith(array $permissions, string $scope = 'global'): Role
{
    $role = Role::factory()->create(['scope' => $scope]);
    $role->permissions()->sync(Permission::query()->whereIn('name', $permissions)->pluck('id'));

    return $role;
}

function organization(?User $owner = null, array $attributes = []): Organization
{
    return app(CreateOrganizationAction::class)->execute(['name' => $attributes['name'] ?? 'Acme'] + $attributes, $owner ?? user());
}

/**
 * Actions missing from a surface, keyed by surface.
 *
 * Every use case must be reachable from the HTTP API, MCP and a UI. The UI
 * surface is the Atrium dashboard plus Roster's user-facing web pages
 * (accepting an invitation is self-service, not an admin screen). An Action
 * counts as reachable when its class name appears in that surface's request
 * or controller classes.
 *
 * @return array<string, array<int, string>>
 */
const PARITY_EXCEPTIONS = [
    // Using the one-time link swaps a browser session; it has no API or
    // MCP meaning. Start, list and stop are on every surface.
    'EnterImpersonationAction' => ['http', 'mcp'],
    // Signing in and linking happen in the browser, between Roster and the
    // identity provider; there is nothing for an API caller to send.
    'SsoLoginAction' => ['http', 'mcp'],
    'LinkSsoIdentityAction' => ['http', 'mcp'],
    // Machine-to-machine sync from an ERP or CRM. In Atrium the same results
    // come from the organization CSV import (bulk) and the link form.
    'SyncOrganizationAction' => ['ui'],
    'SyncOrganizationsAction' => ['ui'],
];

function parityGaps(): array
{
    $root = dirname(__DIR__);

    $actions = array_map(
        fn (string $path): string => basename($path, '.php'),
        (array) glob($root.'/src/Actions/*.php'),
    );

    $surfaces = [
        'http' => $root.'/src/Http/Requests/*.php',
        'mcp' => $root.'/src/Mcp/Requests/*.php',
        'ui' => [$root.'/src/Http/Ui/*.php', $root.'/src/Http/Web/*.php'],
    ];

    $gaps = [];

    foreach ($surfaces as $surface => $patterns) {
        $files = array_merge(...array_map(fn (string $pattern): array => (array) glob($pattern), (array) $patterns));

        $source = implode("\n", array_map(
            fn (string $path): string => (string) file_get_contents($path),
            $files,
        ));

        $missing = array_values(array_filter(
            $actions,
            fn (string $action): bool => ! str_contains($source, $action)
                && ! in_array($surface, PARITY_EXCEPTIONS[$action] ?? [], true),
        ));

        if ($missing !== []) {
            $gaps[$surface] = $missing;
        }
    }

    return $gaps;
}
