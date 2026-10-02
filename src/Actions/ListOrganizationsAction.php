<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use JayI\Roster\Events\Action\OrganizationsListedActionEvent;
use JayI\Roster\Events\Action\OrganizationsListingActionEvent;
use JayI\Roster\Models\Membership;
use JayI\Roster\Models\Organization;
use JayI\Roster\Support\Users;

final class ListOrganizationsAction
{
    public function __construct(private readonly Users $users) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            // `only`: deleted organizations only; `with`: all of them.
            'trashed' => ['sometimes', 'nullable', Rule::in(['only', 'with'])],
            'user' => ['sometimes', 'nullable'],
            'source' => ['sometimes', 'nullable', 'string', 'max:64'],
            'external_id' => ['sometimes', 'nullable', 'string', 'max:191'],
            'account_number' => ['sometimes', 'nullable', 'string', 'max:191'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Organization>
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        OrganizationsListingActionEvent::dispatch($filters);

        $query = match ($filters['trashed'] ?? null) {
            'only' => Organization::onlyTrashed(),
            'with' => Organization::withTrashed(),
            default => Organization::query(),
        };
        $query->with(['domains', 'links', 'owner'])->withCount(['memberships', 'teams']);

        $search = $filters['search'] ?? null;

        if (is_string($search) && $search !== '') {
            $query->where(fn (Builder $builder): Builder => $builder
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('slug', 'like', '%'.$search.'%'));
        }

        if (($filters['user'] ?? null) !== null) {
            $user = $this->users->resolve($filters['user']);

            $query->whereIn('id', Membership::query()->select('organization_id')->where('user_id', $user->getKey()));
        }

        // Find organizations by their records in external systems.
        $link = array_filter(array_intersect_key($filters, array_flip(['source', 'external_id', 'account_number'])), fn (mixed $value): bool => is_string($value) && $value !== '');

        if ($link !== []) {
            $query->whereHas('links', fn (Builder $builder): Builder => $builder->where($link));
        }

        $perPage = is_numeric($filters['per_page'] ?? null) ? (int) $filters['per_page'] : 15;
        $page = is_numeric($filters['page'] ?? null) ? (int) $filters['page'] : null;

        $organizations = $query->orderBy('name')->orderBy('id')->paginate($perPage, ['*'], 'page', $page);

        OrganizationsListedActionEvent::dispatch($filters);

        return $organizations;
    }
}
