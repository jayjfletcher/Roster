<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use JayI\Roster\Events\Action\MembersListedActionEvent;
use JayI\Roster\Events\Action\MembersListingActionEvent;
use JayI\Roster\Models\Membership;
use JayI\Roster\Models\Organization;

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
     * @return LengthAwarePaginator<int, Membership>
     */
    public function execute(Organization $organization, array $filters = []): LengthAwarePaginator
    {
        MembersListingActionEvent::dispatch($organization, $filters);

        $perPage = is_numeric($filters['per_page'] ?? null) ? (int) $filters['per_page'] : 15;
        $page = is_numeric($filters['page'] ?? null) ? (int) $filters['page'] : null;

        $members = $organization->memberships()
            ->with(['user', 'teams', 'organization'])
            ->oldest()
            ->oldest('id')
            ->paginate($perPage, ['*'], 'page', $page);

        MembersListedActionEvent::dispatch($organization, $filters);

        return $members;
    }
}
