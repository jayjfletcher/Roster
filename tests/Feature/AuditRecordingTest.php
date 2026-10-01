<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Notification;
use JayI\Roster\Actions\AcceptInvitationAction;
use JayI\Roster\Actions\AddMemberAction;
use JayI\Roster\Actions\AddTeamMemberAction;
use JayI\Roster\Actions\AssignRoleAction;
use JayI\Roster\Actions\CreateInvitationAction;
use JayI\Roster\Actions\CreateRoleAction;
use JayI\Roster\Actions\CreateTeamAction;
use JayI\Roster\Actions\CreateUserAction;
use JayI\Roster\Actions\DeleteUserAction;
use JayI\Roster\Actions\ListUsersAction;
use JayI\Roster\Actions\RemoveMemberAction;
use JayI\Roster\Actions\RevokeRoleAction;
use JayI\Roster\Actions\ShowUserAction;
use JayI\Roster\Actions\SuspendUserAction;
use JayI\Roster\Actions\UpdateOrganizationAction;
use JayI\Roster\Actions\UpdateProfileAction;
use JayI\Roster\Actions\UpdateRoleAction;
use JayI\Roster\Actions\UpdateUserAction;
use JayI\Roster\Mcp\Tools\SuspendUserTool;
use JayI\Roster\Models\AuditEntry;
use JayI\Roster\Notifications\InvitationNotification;

function lastEntry(): AuditEntry
{
    return AuditEntry::query()->orderByDesc('id')->firstOrFail();
}

it('records a created user with every field as new and the password redacted', function (): void {
    $ada = app(CreateUserAction::class)->execute(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'secret-password']);

    $entry = AuditEntry::query()->where('action', 'user.created')->sole();

    expect($entry->source)->toBe('roster')
        ->and($entry->subject_type)->toBe($ada->getMorphClass())
        ->and($entry->subject_id)->toBe((string) $ada->getKey())
        ->and($entry->subject_label)->toBe('Ada')
        ->and($entry->changes['email'])->toBe([null, 'ada@example.com'])
        ->and($entry->changes['password'])->toBe([null, '[redacted]'])
        ->and(json_encode($entry->toArray()))->not->toContain('secret-password');
});

it('records only the fields that changed', function (): void {
    $ada = user(['name' => 'Ada', 'email' => 'ada@example.com']);

    app(UpdateUserAction::class)->execute($ada, ['name' => 'Ada King', 'password' => 'new-password']);

    expect(lastEntry()->action)->toBe('user.updated')
        ->and(lastEntry()->changes)->toBe([
            'name' => ['Ada', 'Ada King'],
            'password' => ['[redacted]', '[redacted]'],
        ]);
});

it('records profile and status changes on the user', function (): void {
    $ada = user();

    app(UpdateProfileAction::class)->execute($ada, ['bio' => 'Mathematician']);
    expect(lastEntry()->action)->toBe('profile.updated')
        ->and(lastEntry()->changes['profile.bio'])->toBe([null, 'Mathematician']);

    app(SuspendUserAction::class)->execute($ada, ['reason' => 'Chargeback']);
    expect(lastEntry()->action)->toBe('user.suspended')
        ->and(lastEntry()->changes['profile.status'])->toBe(['active', 'suspended'])
        ->and(lastEntry()->context['reason'])->toBe('Chargeback');
});

it('records a deleted user with every field as gone', function (): void {
    $ada = user(['name' => 'Ada']);

    app(DeleteUserAction::class)->execute($ada);

    expect(lastEntry()->action)->toBe('user.deleted')
        ->and(lastEntry()->changes['name'])->toBe(['Ada', null]);
});

it('records organizations, domains, members and teams in the organization scope', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $ada = user();

    app(UpdateOrganizationAction::class)->execute($acme, ['domains' => ['acme.com']]);
    expect(lastEntry()->action)->toBe('organization.updated')
        ->and(lastEntry()->organization_id)->toBe($acme->id)
        ->and(lastEntry()->changes['domains'])->toBe([[], ['acme.com']]);

    app(AddMemberAction::class)->execute($acme, ['user' => $ada->getRouteKey()]);
    expect(lastEntry()->action)->toBe('member.added')
        ->and(lastEntry()->subject_id)->toBe((string) $ada->getKey())
        ->and(lastEntry()->organization_id)->toBe($acme->id)
        ->and(lastEntry()->context['organization']['label'])->toBe('Acme');

    $ops = app(CreateTeamAction::class)->execute($acme, ['name' => 'Ops']);
    app(AddTeamMemberAction::class)->execute($ops, ['user' => $ada->getRouteKey()]);
    expect(lastEntry()->action)->toBe('team_member.added')
        ->and(lastEntry()->organization_id)->toBe($acme->id);

    app(RemoveMemberAction::class)->execute($acme, $ada);
    expect(lastEntry()->action)->toBe('member.removed');
});

it('records invitations and their acceptance', function (): void {
    Notification::fake();
    $acme = organization(attributes: ['name' => 'Acme']);

    app(CreateInvitationAction::class)->execute($acme, ['email' => 'ada@example.com']);
    expect(lastEntry()->action)->toBe('invitation.created')
        ->and(lastEntry()->changes['token_hash'])->toBe([null, '[redacted]'])
        ->and(lastEntry()->changes)->toHaveKey('email');

    $token = '';
    Notification::assertSentOnDemand(InvitationNotification::class, function (InvitationNotification $n) use (&$token): bool {
        $token = $n->token;

        return true;
    });

    app(AcceptInvitationAction::class)->execute(['token' => $token], user(['email' => 'ada@example.com']));

    expect(AuditEntry::query()->where('action', 'invitation.accepted')->exists())->toBeTrue()
        ->and(json_encode(AuditEntry::query()->get()->toArray()))->not->toContain($token);
});

it('records role changes and assignments', function (): void {
    $role = app(CreateRoleAction::class)->execute(['name' => 'Auditor', 'scope' => 'global', 'permissions' => ['roster.users.view']]);
    expect(lastEntry()->action)->toBe('role.created')
        ->and(lastEntry()->changes['permissions'])->toBe([null, ['roster.users.view']]);

    app(UpdateRoleAction::class)->execute($role, ['permissions' => ['roster.users.view', 'roster.users.update']]);
    expect(lastEntry()->changes['permissions'])->toBe([['roster.users.view'], ['roster.users.update', 'roster.users.view']]);

    $ada = user();
    $assignment = app(AssignRoleAction::class)->execute($ada, ['role' => $role->id]);
    expect(lastEntry()->action)->toBe('role.assigned')
        ->and(lastEntry()->subject_id)->toBe((string) $ada->getKey())
        ->and(lastEntry()->context['assignment']['role'])->toBe('auditor');

    app(RevokeRoleAction::class)->execute($assignment);
    expect(lastEntry()->action)->toBe('role.revoked');
});

it('does not record reads', function (): void {
    $ada = user();
    $before = AuditEntry::query()->count();

    app(ListUsersAction::class)->execute();
    app(ShowUserAction::class)->execute($ada);

    expect(AuditEntry::query()->count())->toBe($before);
});

it('captures the actor and surface', function (): void {
    $admin = user();
    $ada = user();
    $this->actingAs($admin);

    $this->postJson(route('roster.users.suspend', $ada->getRouteKey()))->assertOk();
    expect(lastEntry()->surface)->toBe('http')
        ->and((string) lastEntry()->actor_id)->toBe((string) $admin->getKey())
        ->and(lastEntry()->ip)->toBe('127.0.0.1');

    $this->post(route('atrium.roster.users.reactivate', $ada->getRouteKey()));
    expect(lastEntry()->surface)->toBe('atrium');

    mcpTool(SuspendUserTool::class, ['user' => $ada->getRouteKey()])->assertOk();
    expect(lastEntry()->surface)->toBe('mcp');
});

it('records code calls outside a request as cli', function (): void {
    app(SuspendUserAction::class)->execute(user());

    expect(lastEntry()->surface)->toBe('cli')
        ->and(lastEntry()->ip)->toBeNull();
});
