<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use JayI\Roster\Actions\Concerns\ResolvesScopes;
use JayI\Roster\Events\Action\SsoIdentitiesListedActionEvent;
use JayI\Roster\Events\Action\SsoIdentitiesListingActionEvent;
use JayI\Roster\Models\SsoIdentity;
use JayI\Roster\Support\Users;

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
     * @return LengthAwarePaginator<int, SsoIdentity>
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        SsoIdentitiesListingActionEvent::dispatch($filters);

        $query = SsoIdentity::query()->with(['connection.organization', 'user']);

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
