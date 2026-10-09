<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Actions;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\User\Events\UserShowingActionEvent;
use RefactorCircus\Roster\Domains\User\Events\UserShownActionEvent;

final class ShowUserAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(Model $user): Model
    {
        UserShowingActionEvent::dispatch($user);

        $user->loadMissing('rosterProfile');

        UserShownActionEvent::dispatch($user);

        return $user;
    }
}
