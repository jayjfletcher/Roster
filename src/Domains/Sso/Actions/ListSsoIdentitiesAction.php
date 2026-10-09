<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use RefactorCircus\Roster\Domains\Sso\Events\SsoIdentitiesListedActionEvent;
use RefactorCircus\Roster\Domains\Sso\Events\SsoIdentitiesListingActionEvent;
use RefactorCircus\Roster\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Roster\Support\Concerns\ResolvesScopes;
use RefactorCircus\Roster\Support\Users;

final class ListSsoIdentitiesAction
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
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, SsoIdentityModel>
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        SsoIdentitiesListingActionEvent::dispatch($filters);

        $query = SsoIdentityModel::query()->with(['connection.organization', 'user']);

        if (($filters['user'] ?? null) !== null) {
            $query->where('user_id', $this->users->resolve($filters['user'])->getKey());
        }

        $organization = $this->organizationFrom($filters['organization'] ?? null);

        if ($organization !== null) {
            $query->whereHas('connection', fn (Builder $connection): Builder => $connection->where('organization_id', $organization->getKey()));
        }

        $perPage = is_numeric($filters['per_page'] ?? null) ? (int) $filters['per_page'] : 25;
        $page = is_numeric($filters['page'] ?? null) ? (int) $filters['page'] : null;

        $identities = $query->latest()->latest('id')->paginate($perPage, ['*'], 'page', $page);

        SsoIdentitiesListedActionEvent::dispatch($filters);

        return $identities;
    }
}
