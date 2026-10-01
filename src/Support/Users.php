<?php

declare(strict_types=1);

namespace JayI\Roster\Support;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use JayI\Roster\Enums\UserStatus;
use JayI\Roster\Models\Profile;

/**
 * The host application's user model, as configured in `roster.users`.
 *
 * Every Roster Action reaches users through this class, so the model class,
 * key type and column names are resolved in exactly one place.
 */
final class Users
{
    public function __construct(private readonly Repository $config) {}

    /**
     * @return class-string<Model>
     */
    public function model(): string
    {
        $model = $this->config->get('roster.users.model');

        if (! is_string($model) || ! is_subclass_of($model, Model::class)) {
            throw new InvalidArgumentException('[roster.users.model] must be an Eloquent model class.');
        }

        return $model;
    }

    public function newModel(): Model
    {
        $model = $this->model();

        return new $model;
    }

    /**
     * @return Builder<Model>
     */
    public function query(): Builder
    {
        return $this->newModel()->newQuery();
    }

    public function table(): string
    {
        return $this->newModel()->getTable();
    }

    public function routeKeyName(): string
    {
        return $this->newModel()->getRouteKeyName();
    }

    /**
     * Find a user by its route key, the identifier every surface uses.
     */
    public function findOrFail(mixed $key): Model
    {
        return $this->query()->where($this->routeKeyName(), $key)->firstOrFail();
    }

    /**
     * The user model's column for a logical field (`name`, `email`, `password`),
     * or null when the users table has no such column.
     */
    public function column(string $field): ?string
    {
        $column = $this->config->get('roster.users.columns.'.$field, $field);

        return is_string($column) && $column !== '' ? $column : null;
    }

    public function name(Model $user): ?string
    {
        $column = $this->column('name');

        return $column === null ? null : $this->string($user->getAttribute($column));
    }

    public function email(Model $user): ?string
    {
        $column = $this->column('email');

        return $column === null ? null : $this->string($user->getAttribute($column));
    }

    /**
     * The user's profile, created on first access.
     */
    public function profile(Model $user): Profile
    {
        $profile = $user->relationLoaded('rosterProfile') ? $user->getRelation('rosterProfile') : null;

        if (! $profile instanceof Profile) {
            $profile = Profile::query()->firstOrCreate(['user_id' => $user->getKey()]);
            $user->setRelation('rosterProfile', $profile);
        }

        return $profile;
    }

    /**
     * The user's profile row, without creating one.
     */
    public function profileIfExists(Model $user): ?Profile
    {
        $profile = $user->relationLoaded('rosterProfile')
            ? $user->getRelation('rosterProfile')
            : Profile::query()->where('user_id', $user->getKey())->first();

        return $profile instanceof Profile ? $profile : null;
    }

    /**
     * Whether the user has proven they own their email address. Models that
     * do not implement MustVerifyEmail never count as verified.
     */
    public function emailVerified(Model $user): bool
    {
        return $user instanceof MustVerifyEmail && $user->hasVerifiedEmail();
    }

    /**
     * The user's status. A user without a profile row is active.
     */
    public function status(Model $user): UserStatus
    {
        return $this->profileIfExists($user)->status ?? UserStatus::Active;
    }

    private function string(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
