<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RefactorCircus\Roster\Domains\Organization\Actions\CreateOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Actions\JoinOrganizationsByDomainAction;
use RefactorCircus\Roster\Domains\User\Concerns\ProfileRules;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Domains\User\Events\UserCreatedActionEvent;
use RefactorCircus\Roster\Domains\User\Events\UserCreatingActionEvent;
use RefactorCircus\Roster\Domains\User\Models\ProfileModel;
use RefactorCircus\Roster\Domains\User\Services\Approvals;
use RefactorCircus\Roster\Support\Users;

final class CreateUserAction
{
    use ProfileRules;

    public function __construct(private readonly Users $users) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        $users = app(Users::class);
        $email = $users->column('email') ?? 'email';

        $rules = [
            'email' => ['required', 'email', 'max:255', Rule::unique($users->table(), $email)],
            'password' => ['sometimes', 'nullable', 'string', 'min:8', 'max:255'],
            // `pending` makes the account wait for an approver.
            'status' => ['sometimes', Rule::in([UserStatus::Active->value, UserStatus::Pending->value])],
        ];

        if ($users->column('name') !== null) {
            $rules = ['name' => ['required', 'string', 'max:255']] + $rules;
        }

        return $rules + self::profileRules();
    }

    /**
     * Create a user and its profile together.
     *
     * Without a password a random one is set, so the account exists but can
     * only be signed into after a reset. With `roster.organizations.personal`
     * on, the user also gets a personal organization; a user created already
     * verified joins any auto-join organizations owning their domain.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Model
    {
        UserCreatingActionEvent::dispatch($data);

        $user = DB::transaction(function () use ($data): Model {
            $user = $this->users->newModel();

            foreach (['name', 'email'] as $field) {
                $column = $this->users->column($field);

                if ($column !== null && array_key_exists($field, $data)) {
                    $user->setAttribute($column, $data[$field]);
                }
            }

            $password = $this->users->column('password');

            if ($password !== null) {
                $user->setAttribute($password, is_string($data['password'] ?? null) ? $data['password'] : Str::password(32));
            }

            $user->save();

            $pending = ($data['status'] ?? null) === UserStatus::Pending->value;
            $profile = ProfileModel::query()->create(['user_id' => $user->getKey()] + self::profileAttributes($data) + ($pending ? ['status' => UserStatus::Pending, 'status_changed_at' => now()] : []));

            if (config('roster.organizations.personal') === true) {
                $name = $this->users->name($user) ?? $this->users->email($user) ?? 'Personal';

                app(CreateOrganizationAction::class)->execute(['name' => $name], $user, personal: true);
            }

            app(JoinOrganizationsByDomainAction::class)->execute($user);

            return $user->setRelation('rosterProfile', $profile->refresh());
        });

        UserCreatedActionEvent::dispatch($user);

        if ($this->users->status($user) === UserStatus::Pending) {
            app(Approvals::class)->pending($user);
        }

        return $user;
    }
}
