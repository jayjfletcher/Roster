<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use JayI\Roster\Actions\Concerns\ResolvesScopes;
use JayI\Roster\Events\Action\RoleAssignmentsListedActionEvent;
use JayI\Roster\Events\Action\RoleAssignmentsListingActionEvent;
use JayI\Roster\Models\RoleAssignment;
use JayI\Roster\Support\Users;

final class ListRoleAssignmentsAction
{
    use ResolvesScopes;

    public function __construct(private readonly Users $users) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'user' => ['sometimes', 'nullable'],
            'organization' => ['sometimes', 'nullable', 'string'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * Assignments for a user, an organization (including its teams), or both.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, RoleAssignment>
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        RoleAssignmentsListingActionEvent::dispatch($filters);

        $query = RoleAssignment::query()->with(['role', 'user', 'organization', 'team']);

        if (($filters['user'] ?? null) !== null) {
            $query->where('user_id', $this->users->findOrFail($filters['user'])->getKey());
        }

        $organization = $this->organizationFrom($filters['organization'] ?? null);

        if ($organization !== null) {
            $query->where('organization_id', $organization->getKey());
        }

        $perPage = is_numeric($filters['per_page'] ?? null) ? (int) $filters['per_page'] : 50;
        $page = is_numeric($filters['page'] ?? null) ? (int) $filters['page'] : null;

        $assignments = $query->oldest()->oldest('id')->paginate($perPage, ['*'], 'page', $page);

        RoleAssignmentsListedActionEvent::dispatch($filters);

        return $assignments;
    }
}
