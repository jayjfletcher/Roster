<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use JayI\Roster\Domains\Organization\Events\MembersListedActionEvent;
use JayI\Roster\Domains\Organization\Events\MembersListingActionEvent;
use JayI\Roster\Domains\Organization\Models\MembershipModel;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;

final class ListMembersAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, MembershipModel>
     */
    public function execute(OrganizationModel $organization, array $filters = []): LengthAwarePaginator
    {
        MembersListingActionEvent::dispatch($organization, $filters);

        $perPage = is_numeric($filters['per_page'] ?? null) ? (int) $filters['per_page'] : 15;
        $page = is_numeric($filters['page'] ?? null) ? (int) $filters['page'] : null;

        $members = $organization->memberships()
            ->with(['user.rosterProfile', 'teams', 'organization'])
            // Deleted users stay members (so a restore is complete) but aren't listed.
            ->whereHas('user')
            ->oldest()
            ->oldest('id')
            ->paginate($perPage, ['*'], 'page', $page);

        MembersListedActionEvent::dispatch($organization, $filters);

        return $members;
    }
}
