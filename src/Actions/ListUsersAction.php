<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use JayI\Roster\Enums\UserStatus;
use JayI\Roster\Events\Action\UsersListedActionEvent;
use JayI\Roster\Events\Action\UsersListingActionEvent;
use JayI\Roster\Models\Profile;
use JayI\Roster\Roster;
use JayI\Roster\Support\Users;

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

        $query = $this->users->query()->with('rosterProfile');
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
                    Profile::query()->select('user_id')->where('display_name', 'like', '%'.$search.'%'),
                );
            });
        }

        $status = isset($filters['status']) ? UserStatus::tryFrom((string) $filters['status']) : null;

        // A user without a profile row is active, so "active" means "no
        // profile with another status" rather than "a profile marked active".
        if ($status === UserStatus::Active) {
            $query->whereNotIn($key, Profile::query()->select('user_id')->where('status', '!=', UserStatus::Active));
        } elseif ($status !== null) {
            $query->whereIn($key, Profile::query()->select('user_id')->where('status', $status));
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
