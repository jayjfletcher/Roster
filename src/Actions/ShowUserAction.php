<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Events\Action\UserShowingActionEvent;
use JayI\Roster\Events\Action\UserShownActionEvent;

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
