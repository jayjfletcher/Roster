<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Notification;
use JayI\Roster\Actions\AddMemberAction;
use JayI\Roster\Actions\CreateTeamAction;
use JayI\Roster\Mcp\Tools\AcceptInvitationTool;
use JayI\Roster\Mcp\Tools\AddMemberTool;
use JayI\Roster\Mcp\Tools\AddTeamMemberTool;
use JayI\Roster\Mcp\Tools\CreateInvitationTool;
use JayI\Roster\Mcp\Tools\CreateOrganizationTool;
use JayI\Roster\Mcp\Tools\CreateTeamTool;
use JayI\Roster\Mcp\Tools\DeclineInvitationTool;
use JayI\Roster\Mcp\Tools\DeleteOrganizationTool;
use JayI\Roster\Mcp\Tools\DeleteTeamTool;
use JayI\Roster\Mcp\Tools\JoinByDomainTool;
use JayI\Roster\Mcp\Tools\ListInvitationsTool;
use JayI\Roster\Mcp\Tools\ListMembersTool;
use JayI\Roster\Mcp\Tools\ListOrganizationsTool;
use JayI\Roster\Mcp\Tools\ListTeamsTool;
use JayI\Roster\Mcp\Tools\RemoveMemberTool;
use JayI\Roster\Mcp\Tools\RemoveTeamMemberTool;
use JayI\Roster\Mcp\Tools\RevokeInvitationTool;
use JayI\Roster\Mcp\Tools\ShowOrganizationTool;
use JayI\Roster\Mcp\Tools\ShowTeamTool;
use JayI\Roster\Mcp\Tools\SwitchContextTool;
use JayI\Roster\Mcp\Tools\TransferOwnershipTool;
use JayI\Roster\Mcp\Tools\UpdateOrganizationTool;
use JayI\Roster\Mcp\Tools\UpdateTeamTool;
use JayI\Roster\Models\Invitation;
use JayI\Roster\Models\Organization;
use JayI\Roster\Notifications\InvitationNotification;

function httpData(string $route, array $parameters = []): array
{
    return test()->getJson(route($route, $parameters))->json();
}

it('lists organizations, members, teams and invitations with parity, including empty lists', function (): void {
    mcpTool(ListOrganizationsTool::class)->assertOk()->assertStructuredContent([
        'data' => [],
        'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 15, 'total' => 0],
    ]);

    $organization = organization();
    app(CreateTeamAction::class)->execute($organization, ['name' => 'Ops']);

    mcpTool(ListOrganizationsTool::class)->assertOk()->assertStructuredContent(httpData('roster.organizations.index')['data'] === [] ? [] : [
        'data' => httpData('roster.organizations.index')['data'],
        'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 15, 'total' => 1],
    ]);

    foreach ([
        [ListMembersTool::class, 'roster.organizations.members.index'],
        [ListTeamsTool::class, 'roster.organizations.teams.index'],
        [ListInvitationsTool::class, 'roster.organizations.invitations.index'],
    ] as [$tool, $route]) {
        $http = httpData($route, [$organization->slug]);

        mcpTool($tool, ['organization' => $organization->slug])->assertOk()->assertStructuredContent([
            'data' => $http['data'],
            'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 15, 'total' => $http['meta']['total']],
        ]);
    }
});

it('manages an organization with parity to the http payload', function (): void {
    $owner = user();
    $ada = user();

    mcpTool(CreateOrganizationTool::class, ['name' => 'Acme', 'owner' => $owner->getRouteKey()])
        ->assertOk()
        ->assertStructuredContent(httpData('roster.organizations.show', ['acme']));

    mcpTool(UpdateOrganizationTool::class, ['organization' => 'acme', 'domains' => ['acme.com']])
        ->assertOk()
        ->assertStructuredContent(httpData('roster.organizations.show', ['acme']));

    mcpTool(ShowOrganizationTool::class, ['organization' => 'acme'])
        ->assertOk()
        ->assertStructuredContent(httpData('roster.organizations.show', ['acme']));

    mcpTool(AddMemberTool::class, ['organization' => 'acme', 'user' => $ada->getRouteKey()])->assertOk();

    mcpTool(TransferOwnershipTool::class, ['organization' => 'acme', 'user' => $ada->getRouteKey()])
        ->assertOk()
        ->assertStructuredContent(httpData('roster.organizations.show', ['acme']));

    mcpTool(RemoveMemberTool::class, ['organization' => 'acme', 'user' => $owner->getRouteKey()])->assertOk()->assertSee('Member removed.');

    mcpTool(DeleteOrganizationTool::class, ['organization' => 'acme'])->assertOk();

    expect(Organization::query()->count())->toBe(0);

    mcpTool(ShowOrganizationTool::class, ['organization' => 'acme'])->assertHasErrors(['Not found.']);
});

it('manages teams with parity to the http payload', function (): void {
    $organization = organization();
    $ada = user();
    app(AddMemberAction::class)->execute($organization, ['user' => $ada->getRouteKey()]);
    $args = ['organization' => $organization->slug];

    mcpTool(CreateTeamTool::class, $args + ['name' => 'Ops'])
        ->assertOk()
        ->assertStructuredContent(httpData('roster.organizations.teams.show', [$organization->slug, 'ops']));

    mcpTool(AddTeamMemberTool::class, $args + ['team' => 'ops', 'user' => $ada->getRouteKey()])
        ->assertOk()
        ->assertStructuredContent(httpData('roster.organizations.teams.show', [$organization->slug, 'ops']));

    mcpTool(UpdateTeamTool::class, $args + ['team' => 'ops', 'slug' => 'operations'])
        ->assertOk()
        ->assertStructuredContent(httpData('roster.organizations.teams.show', [$organization->slug, 'operations']));

    mcpTool(ShowTeamTool::class, $args + ['team' => 'operations'])
        ->assertOk()
        ->assertStructuredContent(httpData('roster.organizations.teams.show', [$organization->slug, 'operations']));

    mcpTool(RemoveTeamMemberTool::class, $args + ['team' => 'operations', 'user' => $ada->getRouteKey()])->assertOk();

    mcpTool(DeleteTeamTool::class, $args + ['team' => 'operations'])->assertOk()->assertSee('Team deleted.');
});

it('runs the invitation lifecycle', function (): void {
    Notification::fake();
    $organization = organization();

    mcpTool(CreateInvitationTool::class, ['organization' => $organization->slug, 'email' => 'ada@example.com'])->assertOk();
    mcpTool(CreateInvitationTool::class, ['organization' => $organization->slug, 'email' => 'eve@example.com'])->assertOk();

    $tokens = [];
    Notification::assertSentOnDemand(InvitationNotification::class, function (InvitationNotification $notification) use (&$tokens): bool {
        $tokens[$notification->invitation->email] = $notification->token;

        return true;
    });

    // Answering acts as the authenticated user.
    mcpTool(AcceptInvitationTool::class, ['token' => $tokens['ada@example.com']])->assertHasErrors(['Unauthorized.']);

    $this->actingAs(user(['email' => 'ada@example.com']));
    mcpTool(AcceptInvitationTool::class, ['token' => $tokens['ada@example.com']])->assertOk();

    $this->actingAs(user(['email' => 'eve@example.com']));
    mcpTool(DeclineInvitationTool::class, ['token' => $tokens['eve@example.com']])->assertOk();

    $grace = Invitation::query()->create([
        'organization_id' => $organization->getKey(),
        'email' => 'grace@example.com',
        'token_hash' => hash('sha256', 'x'),
        'expires_at' => now()->addDay(),
    ]);

    mcpTool(RevokeInvitationTool::class, ['organization' => $organization->slug, 'invitation' => $grace->id])->assertOk();

    expect(Invitation::query()->get()->map(fn (Invitation $invitation): string => $invitation->status()->value)->sort()->values()->all())
        ->toBe(['accepted', 'declined', 'revoked']);
});

it('switches context and runs domain join', function (): void {
    $ada = user(['email' => 'ada@acme.com']);
    organization(attributes: ['name' => 'Acme', 'domains' => ['acme.com'], 'auto_join' => true]);

    mcpTool(JoinByDomainTool::class, ['user' => $ada->getRouteKey()])->assertOk()->assertSee('acme');

    mcpTool(SwitchContextTool::class, ['user' => $ada->getRouteKey(), 'organization' => 'acme'])
        ->assertOk()
        ->assertStructuredContent(httpData('roster.users.show', [$ada->getRouteKey()]));
});
