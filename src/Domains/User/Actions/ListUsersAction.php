<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Domains\User\Events\UsersListedActionEvent;
use RefactorCircus\Roster\Domains\User\Events\UsersListingActionEvent;
use RefactorCircus\Roster\Domains\User\Models\ProfileModel;
use RefactorCircus\Roster\Roster;
use RefactorCircus\Roster\Support\Users;

final class ListUsersAction
{
    public function __construct(private readonly Users $users) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', Rule::enum(UserStatus::class)],
            // `only`: deleted users only; `with`: everyone. Needs a soft-deleting user model.
            'trashed' => ['sometimes', 'nullable', Rule::in(['only', 'with'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Model>
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        UsersListingActionEvent::dispatch($filters);

        $query = match ($filters['trashed'] ?? null) {
            'only' => $this->users->onlyTrashed(),
            'with' => $this->users->withTrashed(),
            default => $this->users->query(),
        };
        $query->with('rosterProfile');
        $key = $this->users->newModel()->getQualifiedKeyName();

        $search = $filters['search'] ?? null;

        if (is_string($search) && $search !== '') {
            $columns = array_filter([$this->users->column('name'), $this->users->column('email')]);

            $query->where(function (Builder $builder) use ($columns, $search): void {
                foreach ($columns as $column) {
                    $builder->orWhere($column, 'like', '%'.$search.'%');
                }

                $builder->orWhereIn(
                    $builder->getModel()->getQualifiedKeyName(),
                    ProfileModel::query()->select('user_id')->where('display_name', 'like', '%'.$search.'%'),
                );
            });
        }

        $status = isset($filters['status']) ? UserStatus::tryFrom((string) $filters['status']) : null;

        // A user without a profile row is active, so "active" means "no
        // profile with another status" rather than "a profile marked active".
        if ($status === UserStatus::Active) {
            $query->whereNotIn($key, ProfileModel::query()->select('user_id')->where('status', '!=', UserStatus::Active));
        } elseif ($status !== null) {
            $query->whereIn($key, ProfileModel::query()->select('user_id')->where('status', $status));
        }

        $perPage = is_numeric($filters['per_page'] ?? null) ? (int) $filters['per_page'] : 15;
        $page = is_numeric($filters['page'] ?? null) ? (int) $filters['page'] : null;

        $users = $query->orderBy($key)->paginate($perPage, ['*'], 'page', $page);

        // Each user's current organization and team, for the whole page at once.
        app(Roster::class)->preload($users->getCollection());

        UsersListedActionEvent::dispatch($filters);

        return $users;
    }
}
