<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RefactorCircus\Roster\Domains\Sso\Events\SsoConnectionsListedActionEvent;
use RefactorCircus\Roster\Domains\Sso\Events\SsoConnectionsListingActionEvent;
use RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Roster\Support\Concerns\ResolvesScopes;

final class ListSsoConnectionsAction
{
    use ResolvesScopes;

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'organization' => ['sometimes', 'nullable', 'string'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, SsoConnectionModel>
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        SsoConnectionsListingActionEvent::dispatch($filters);

        $query = SsoConnectionModel::query()->with('organization')->withCount('identities');
        $organization = $this->organizationFrom($filters['organization'] ?? null);

        if ($organization !== null) {
            $query->where('organization_id', $organization->getKey());
        }

        $perPage = is_numeric($filters['per_page'] ?? null) ? (int) $filters['per_page'] : 25;
        $page = is_numeric($filters['page'] ?? null) ? (int) $filters['page'] : null;

        $connections = $query->orderBy('name')->orderBy('id')->paginate($perPage, ['*'], 'page', $page);

        SsoConnectionsListedActionEvent::dispatch($filters);

        return $connections;
    }
}
