<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Domains\Impersonation\Services\ImpersonationContext;
use JayI\Roster\Domains\User\Events\UserUpdatedActionEvent;
use JayI\Roster\Domains\User\Events\UserUpdatingActionEvent;
use JayI\Roster\Support\Users;

final class UpdateUserAction
{
    public function __construct(private readonly Users $users) {}

    /**
     * Pass the user being updated so its own email passes the unique check.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(?Model $user = null): array
    {
        $users = app(Users::class);
        $unique = Rule::unique($users->table(), $users->column('email') ?? 'email');

        if ($user !== null) {
            $unique->ignore($user->getKey(), $user->getKeyName());
        }

        $rules = [
            'email' => ['sometimes', 'required', 'email', 'max:255', $unique],
            'password' => ['sometimes', 'required', 'string', 'min:8', 'max:255'],
        ];

        if ($users->column('name') !== null) {
            $rules = ['name' => ['sometimes', 'required', 'string', 'max:255']] + $rules;
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Model $user, array $data): Model
    {
        // An impersonator must not be able to take over the account.
        $impersonated = app(ImpersonationContext::class)->active()?->user_id;

        if ($impersonated !== null && (string) $impersonated === (string) $user->getKey()) {
            foreach (['email', 'password'] as $field) {
                if (array_key_exists($field, $data)) {
                    throw ValidationException::withMessages([$field => __('roster::roster.impersonation_account_locked')]);
                }
            }
        }

        UserUpdatingActionEvent::dispatch($user, $data);

        DB::transaction(function () use ($user, $data): void {
            foreach (['name', 'email', 'password'] as $field) {
                $column = $this->users->column($field);

                if ($column !== null && array_key_exists($field, $data)) {
                    $user->setAttribute($column, $data[$field]);
                }
            }

            $user->save();
        });

        $user = $user->refresh()->load('rosterProfile');

        UserUpdatedActionEvent::dispatch($user);

        return $user;
    }
}
