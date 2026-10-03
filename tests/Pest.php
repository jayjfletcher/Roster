<?php

declare(strict_types=1);

use JayI\Roster\Domains\Organization\Actions\CreateOrganizationAction;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Permission\Models\PermissionModel;
use JayI\Roster\Domains\Permission\Services\Permissions;
use JayI\Roster\Domains\Role\Models\RoleAssignmentModel;
use JayI\Roster\Domains\Role\Models\RoleModel;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Mcp\RosterServer;
use JayI\Roster\Tests\AtriumFeaturesTestCase;
use JayI\Roster\Tests\AuditOffTestCase;
use JayI\Roster\Tests\AuthorizationTestCase;
use JayI\Roster\Tests\BrowserTestCase;
use JayI\Roster\Tests\CortexTestCase;
use JayI\Roster\Tests\DefaultsTestCase;
use JayI\Roster\Tests\PennantPlusTestCase;
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

// pest-plugin-browser removes its output directories with @rmdir() when it
// boots. A missing directory raises a (suppressed) warning that failOnWarning
// turns into a failed run, even with every test passing, so make sure each
// one it removes exists; empty directories remove cleanly.
foreach (['Traces', 'Screenshots', 'Screenshots/Sliders', 'Screenshots/ImageDiffView'] as $directory) {
    if (! is_dir(__DIR__.'/Browser/'.$directory)) {
        @mkdir(__DIR__.'/Browser/'.$directory, recursive: true);
    }
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
uses(AtriumFeaturesTestCase::class)->in('Modes/AtriumFeatures');
uses(PennantPlusTestCase::class)->in('Modes/PennantPlus');
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
function grant(User $user, string $slug, ?OrganizationModel $organization = null, ?TeamModel $team = null): void
{
    $role = RoleModel::query()->where('slug', $slug)->firstOrFail();

    RoleAssignmentModel::query()->create([
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
function roleWith(array $permissions, string $scope = 'global'): RoleModel
{
    $role = RoleModel::factory()->create(['scope' => $scope]);
    $role->permissions()->sync(PermissionModel::query()->whereIn('name', $permissions)->pluck('id'));

    return $role;
}

function organization(?User $owner = null, array $attributes = []): OrganizationModel
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
        (array) glob($root.'/src/Domains/*/Actions/*.php'),
    );

    $surfaces = [
        'http' => $root.'/src/Domains/*/Http/Requests/*.php',
        'mcp' => $root.'/src/Domains/*/Mcp/Requests/*.php',
        'ui' => [
            $root.'/src/Atrium/Http/Controllers/*.php',
            $root.'/src/Domains/*/Http/Controllers/*WebController.php',
            $root.'/src/Domains/*/Http/Controllers/TransferFileController.php',
        ],
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
