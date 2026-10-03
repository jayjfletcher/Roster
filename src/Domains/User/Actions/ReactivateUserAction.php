<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use JayI\Roster\Domains\User\Concerns\ChangesStatus;
use JayI\Roster\Domains\User\Enums\UserStatus;
use JayI\Roster\Domains\User\Events\UserReactivatedActionEvent;
use JayI\Roster\Domains\User\Events\UserReactivatingActionEvent;

final class ReactivateUserAction
{
    use ChangesStatus;

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    /**
     * Return a suspended or deactivated user to active, clearing the reason.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Model $user, array $data = [], ?Model $actor = null): Model
    {
        $this->guardTransition($user, UserStatus::Active, $actor);

        UserReactivatingActionEvent::dispatch($user);

        DB::transaction(fn () => $this->changeStatus($user, UserStatus::Active, null));

        $user = $user->load('rosterProfile');

        UserReactivatedActionEvent::dispatch($user);

        return $user;
    }
}
