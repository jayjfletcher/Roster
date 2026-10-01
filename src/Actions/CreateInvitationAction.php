<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Events\Action\InvitationCreatedActionEvent;
use JayI\Roster\Events\Action\InvitationCreatingActionEvent;
use JayI\Roster\Models\Invitation;
use JayI\Roster\Models\Organization;
use JayI\Roster\Notifications\InvitationNotification;
use JayI\Roster\Support\InvitationTokens;
use JayI\Roster\Support\Users;

final class CreateInvitationAction
{
    public function __construct(private readonly Users $users) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'teams' => ['sometimes', 'array', 'max:50'],
            'teams.*' => ['string', 'distinct'],
        ];
    }

    /**
     * Invite an email address into the organization, optionally onto some
     * of its teams (by slug), and email the link.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Organization $organization, array $data, ?Model $actor = null): Invitation
    {
        $email = strtolower((string) $data['email']);
        $teams = $this->teamIds($organization, (array) ($data['teams'] ?? []));

        $this->guard($organization, $email);

        InvitationCreatingActionEvent::dispatch($organization, $data);

        $token = InvitationTokens::generate();

        $invitation = DB::transaction(fn (): Invitation => Invitation::query()->create([
            'organization_id' => $organization->getKey(),
            'email' => $email,
            'token_hash' => InvitationTokens::hash($token),
            'teams' => $teams,
            'invited_by' => $actor?->getKey(),
            'expires_at' => now()->addDays((int) config('roster.invitations.expires_after_days', 7)),
        ]));

        $invitation->load('organization');

        Notification::route('mail', $email)->notify(new InvitationNotification($invitation, $token));

        InvitationCreatedActionEvent::dispatch($invitation);

        return $invitation;
    }

    private function guard(Organization $organization, string $email): void
    {
        $column = $this->users->column('email');
        // whereLike is case-insensitive; the exact comparison drops any match
        // a `_` or `%` in the address let through.
        $existing = $column === null ? null : $this->users->query()
            ->whereLike($column, $email)
            ->get()
            ->first(fn (Model $user): bool => strcasecmp((string) $this->users->email($user), $email) === 0);

        if ($existing !== null && $organization->membershipFor($existing) !== null) {
            throw ValidationException::withMessages(['email' => __('roster::roster.already_a_member')]);
        }

        if ($organization->invitations()->pending()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => __('roster::roster.invitation_already_pending')]);
        }
    }

    /**
     * @param  array<int, mixed>  $slugs
     * @return array<int, string>
     */
    private function teamIds(Organization $organization, array $slugs): array
    {
        if ($slugs === []) {
            return [];
        }

        $teams = $organization->teams()->whereIn('slug', $slugs)->pluck('id', 'slug');

        if ($teams->count() !== count($slugs)) {
            throw ValidationException::withMessages(['teams' => __('roster::roster.teams_not_in_organization')]);
        }

        return array_values(array_map('strval', $teams->all()));
    }
}
