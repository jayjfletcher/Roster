<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Impersonation\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use JayI\Roster\Domains\Impersonation\Events\ImpersonationsListedActionEvent;
use JayI\Roster\Domains\Impersonation\Events\ImpersonationsListingActionEvent;
use JayI\Roster\Domains\Impersonation\Models\ImpersonationModel;
use JayI\Roster\Support\Concerns\ResolvesScopes;
use JayI\Roster\Support\Users;

final class ListImpersonationsAction
{
    use ResolvesScopes;

    public function __construct(private readonly Users $users) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'active' => ['sometimes', 'boolean'],
            'user' => ['sometimes', 'nullable'],
            'impersonator' => ['sometimes', 'nullable'],
            'organization' => ['sometimes', 'nullable', 'string'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * Newest first.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<int, int|string>|null  $organizations  Only these organizations; null for no limit.
     * @return LengthAwarePaginator<int, ImpersonationModel>
     */
    public function execute(array $filters = [], ?array $organizations = null): LengthAwarePaginator
    {
        ImpersonationsListingActionEvent::dispatch($filters);

        $query = ImpersonationModel::query()->with(['user', 'impersonator', 'organization']);

        if (filter_var($filters['active'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $query->active();
        }

        foreach (['user' => 'user_id', 'impersonator' => 'impersonator_id'] as $filter => $column) {
            if (($filters[$filter] ?? null) !== null) {
                $query->where($column, $this->users->findOrFail($filters[$filter])->getKey());
            }
        }

        $organization = $this->organizationFrom($filters['organization'] ?? null);

        if ($organization !== null) {
            $query->where('organization_id', $organization->getKey());
        }

        if ($organizations !== null) {
            $query->whereIn('organization_id', $organizations);
        }

        $perPage = is_numeric($filters['per_page'] ?? null) ? (int) $filters['per_page'] : 25;
        $page = is_numeric($filters['page'] ?? null) ? (int) $filters['page'] : null;

        $impersonations = $query->latest()->latest('id')->paginate($perPage, ['*'], 'page', $page);

        ImpersonationsListedActionEvent::dispatch($filters);

        return $impersonations;
    }
}
