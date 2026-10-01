<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Actions\Concerns\ManagesMemberships;
use JayI\Roster\Enums\MembershipSource;
use JayI\Roster\Events\Action\MemberAddedActionEvent;
use JayI\Roster\Events\Action\MemberAddingActionEvent;
use JayI\Roster\Models\Membership;
use JayI\Roster\Models\Organization;
use JayI\Roster\Support\Users;

final class AddMemberAction
{
    use ManagesMemberships;

    public function __construct(private readonly Users $users) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'user' => ['required'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Organization $organization, array $data): Membership
    {
        $user = $this->users->findOrFail($data['user'] ?? null);

        if ($organization->membershipFor($user) !== null) {
            throw ValidationException::withMessages(['user' => __('roster::roster.already_a_member')]);
        }

        MemberAddingActionEvent::dispatch($organization, $user);

        $membership = DB::transaction(fn (): Membership => $this->join($organization, $user, MembershipSource::Direct));

        MemberAddedActionEvent::dispatch($organization, $user);

        return $membership->load(['user', 'teams', 'organization']);
    }
}
