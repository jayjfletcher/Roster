<?php

declare(strict_types=1);

require_once __DIR__.'/Scim/helpers.php';

use Illuminate\Support\Facades\DB;
use JayI\Roster\Actions\AddMemberAction;
use JayI\Roster\Actions\AddTeamMemberAction;
use JayI\Roster\Actions\ConfirmImportAction;
use JayI\Roster\Actions\CreateScimTokenAction;
use JayI\Roster\Actions\CreateTeamAction;
use JayI\Roster\Actions\StartExportAction;
use JayI\Roster\Actions\StartImportAction;
use JayI\Roster\Actions\SwitchContextAction;
use JayI\Roster\Actions\SyncOrganizationsAction;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Transfer;
use Workbench\App\Models\User;

/*
 * Query budgets: the paths that run on every request, or once per row of a
 * list or a bulk job, must not grow with the number of rows. Each test
 * measures the same path at two sizes.
 */

function queries(Closure $callback): int
{
    // A fresh request's worth of per-request caches.
    app()->forgetScopedInstances();
    DB::flushQueryLog();
    DB::enableQueryLog();
    $callback();
    DB::disableQueryLog();

    return count(DB::getQueryLog());
}

/**
 * Acme (on acme.test) with team Ops and `$count` members seated on it, each
 * with Acme/Ops as their current context.
 *
 * @return array{0: User, 1: Organization}
 */
function crowd(int $count): array
{
    $owner = user(['email' => 'owner@acme.test']);
    $acme = organization($owner, ['name' => 'Acme', 'domains' => ['acme.test']]);
    $ops = app(CreateTeamAction::class)->execute($acme, ['name' => 'Ops']);

    foreach (range(1, $count) as $i) {
        $member = user(['email' => "m{$i}@acme.test"]);
        app(AddMemberAction::class)->execute($acme, ['user' => $member->getRouteKey()]);
        app(AddTeamMemberAction::class)->execute($ops, ['user' => $member->getRouteKey()]);
        app(SwitchContextAction::class)->execute($member, ['organization' => 'acme', 'team' => 'ops']);
    }

    return [$owner, $acme];
}

it('resolves Gate checks without a scope once per request', function (): void {
    crowd(1);
    $member = User::query()->where('email', 'm1@acme.test')->firstOrFail();

    $checks = fn (int $times): int => queries(function () use ($member, $times): void {
        $fresh = $member->fresh();

        foreach (range(1, $times) as $ignored) {
            $fresh->can('roster.members.view');
        }
    });

    expect($checks(20))->toBe($checks(5))->toBeLessThanOrEqual(10);
});

it('lists users with context in a fixed number of queries', function (int $size): void {
    [$owner] = crowd($size);
    $this->actingAs($owner);

    expect(queries(fn () => $this->getJson(route('roster.users.index', ['per_page' => 100]))->assertOk()))->toBeLessThanOrEqual(8);
})->with([5, 30]);

it('renders each organization tab in a fixed number of queries', function (string $tab): void {
    [$owner] = crowd(30);
    $this->actingAs($owner);

    expect(queries(fn () => $this->get(route('atrium.roster.organizations.show', ['acme', 'tab' => $tab]))->assertOk()))->toBeLessThanOrEqual(16);
})->with(['members', 'teams', 'invitations', 'roles', 'sso', 'scim', 'activity', 'settings']);

it('lists transfers in a fixed number of queries', function (int $size): void {
    $owner = user();
    Transfer::factory()->count($size)->create(['requested_by' => $owner->getKey(), 'output_path' => 'missing.csv']);
    $this->actingAs($owner);

    expect(queries(fn () => $this->getJson(route('roster.transfers.index'))->assertOk()))->toBeLessThanOrEqual(6);
})->with([2, 20]);

it('exports members in a fixed number of queries per chunk', function (int $size): void {
    [$owner] = crowd($size);

    expect(queries(fn () => app(StartExportAction::class)->execute(['type' => 'export_members', 'organization' => 'acme'], $owner)))->toBeLessThanOrEqual(55);
})->with([5, 30]);

it('previews imports with at most a couple of queries per row', function (): void {
    [$owner] = crowd(0);
    $csv = fn (int $rows): string => "email,name,teams\n".implode("\n", array_map(fn (int $i): string => "new{$i}@acme.test,New {$i},ops", range(1, $rows)));

    $ten = queries(fn () => app(StartImportAction::class)->execute(['type' => 'import_members', 'organization' => 'acme', 'content' => $csv(10)], $owner));
    $forty = queries(fn () => app(StartImportAction::class)->execute(['type' => 'import_members', 'organization' => 'acme', 'content' => $csv(40)], $owner));

    expect(($forty - $ten) / 30)->toBeLessThanOrEqual(2);
});

it('applies imports with a bounded number of queries per row', function (): void {
    [$owner] = crowd(0);
    $import = fn (int $rows, string $prefix): Transfer => app(StartImportAction::class)->execute([
        'type' => 'import_members',
        'organization' => 'acme',
        'content' => "email,name,teams\n".implode("\n", array_map(fn (int $i): string => "{$prefix}{$i}@acme.test,New {$i},ops", range(1, $rows))),
    ], $owner);

    $ten = $import(10, 'a');
    $forty = $import(40, 'b');

    $perRow = (queries(fn () => app(ConfirmImportAction::class)->execute($forty, $owner)) - queries(fn () => app(ConfirmImportAction::class)->execute($ten, $owner))) / 30;

    // Most of a row is its writes and their audit entries; reads are cached.
    expect($perRow)->toBeLessThanOrEqual(42);
});

it('re-syncs unchanged organizations cheaply', function (): void {
    $records = array_map(fn (int $i): array => ['source' => 'erp', 'external_id' => "C-{$i}", 'name' => "Org {$i}"], range(1, 20));
    app(SyncOrganizationsAction::class)->execute(['records' => $records]);

    expect(queries(fn () => app(SyncOrganizationsAction::class)->execute(['records' => $records])) / 20)->toBeLessThanOrEqual(6);
});

it('serves a SCIM users page in a fixed number of queries', function (int $size): void {
    [, $acme] = crowd(0);
    $token = app(CreateScimTokenAction::class)->execute($acme, ['name' => 'Okta'])->plain;

    foreach (range(1, $size) as $i) {
        $this->postJson('/scim/v2/acme/Users', oktaUser("s{$i}@acme.test", "ext{$i}"), ['Authorization' => 'Bearer '.$token])->assertCreated();
    }

    expect(queries(fn () => $this->getJson('/scim/v2/acme/Users?count=100', ['Authorization' => 'Bearer '.$token])->assertOk()->assertJsonPath('totalResults', $size)))
        ->toBeLessThanOrEqual(10);
})->with([3, 30]);

it('serves a SCIM groups page in a fixed number of queries', function (int $size): void {
    [, $acme] = crowd(0);
    $token = app(CreateScimTokenAction::class)->execute($acme, ['name' => 'Okta'])->plain;
    $headers = ['Authorization' => 'Bearer '.$token];
    $ids = [];

    foreach (range(1, 4) as $i) {
        $ids[] = ['value' => $this->postJson('/scim/v2/acme/Users', oktaUser("s{$i}@acme.test", "ext{$i}"), $headers)->assertCreated()->json('id')];
    }

    foreach (range(1, $size) as $i) {
        $this->postJson('/scim/v2/acme/Groups', [
            'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:Group'],
            'displayName' => "Group {$i}",
            'members' => $ids,
        ], $headers)->assertCreated();
    }

    expect(queries(fn () => $this->getJson('/scim/v2/acme/Groups?count=100', $headers)->assertOk()->assertJsonPath('totalResults', $size)->assertJsonCount(4, 'Resources.0.members')))
        ->toBeLessThanOrEqual(10);
})->with([2, 15]);
