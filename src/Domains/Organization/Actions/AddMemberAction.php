<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Domains\Organization\Concerns\ManagesMemberships;
use JayI\Roster\Domains\Organization\Enums\MembershipSource;
use JayI\Roster\Domains\Organization\Events\MemberAddedActionEvent;
use JayI\Roster\Domains\Organization\Events\MemberAddingActionEvent;
use JayI\Roster\Domains\Organization\Models\MembershipModel;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
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
    public function execute(OrganizationModel $organization, array $data): MembershipModel
    {
        $user = $this->users->findOrFail($data['user'] ?? null);

        if ($organization->membershipFor($user) !== null) {
            throw ValidationException::withMessages(['user' => __('roster::roster.already_a_member')]);
        }

        MemberAddingActionEvent::dispatch($organization, $user);

        $membership = DB::transaction(fn (): MembershipModel => $this->join($organization, $user, MembershipSource::Direct));

        MemberAddedActionEvent::dispatch($organization, $user);

        return $membership->load(['user', 'teams', 'organization']);
    }
}
