<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use RefactorCircus\Roster\Domains\Role\Enums\RoleScope;
use RefactorCircus\Roster\Domains\Role\Events\RolesListedActionEvent;
use RefactorCircus\Roster\Domains\Role\Events\RolesListingActionEvent;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Support\Concerns\ResolvesScopes;

final class ListRolesAction
{
    use ResolvesScopes;

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'scope' => ['sometimes', 'nullable', Rule::enum(RoleScope::class)],
            'organization' => ['sometimes', 'nullable', 'string'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * Roles by scope and name. With `organization`, the roles usable in that
     * organization: the shared ones plus its own. Without, the shared ones.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<int, int|string>|null  $organizations  Only these organizations; null for no limit.
     * @return LengthAwarePaginator<int, RoleModel>
     */
    public function execute(array $filters = [], ?array $organizations = null): LengthAwarePaginator
    {
        RolesListingActionEvent::dispatch($filters);

        $organization = $this->organizationFrom($filters['organization'] ?? null);
        $scope = isset($filters['scope']) ? RoleScope::tryFrom((string) $filters['scope']) : null;

        $query = RoleModel::query()
            ->with(['permissions', 'organization'])
            // Shared roles, plus the organization's own - or, for a list limited to
            // some organizations, their own.
            ->where(fn (Builder $builder): Builder => match (true) {
                $organization !== null => $builder->whereNull('organization_id')->orWhere('organization_id', $organization->getKey()),
                $organizations !== null => $builder->whereNull('organization_id')->orWhereIn('organization_id', $organizations),
                default => $builder->whereNull('organization_id'),
            });

        if ($scope !== null) {
            $query->where('scope', $scope);
        }

        $perPage = is_numeric($filters['per_page'] ?? null) ? (int) $filters['per_page'] : 50;
        $page = is_numeric($filters['page'] ?? null) ? (int) $filters['page'] : null;

        $roles = $query->orderBy('scope')->orderBy('name')->orderBy('id')->paginate($perPage, ['*'], 'page', $page);

        RolesListedActionEvent::dispatch($filters);

        return $roles;
    }
}
