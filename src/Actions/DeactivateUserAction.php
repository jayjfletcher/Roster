<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use JayI\Roster\Actions\Concerns\ChangesStatus;
use JayI\Roster\Enums\UserStatus;
use JayI\Roster\Events\Action\UserDeactivatedActionEvent;
use JayI\Roster\Events\Action\UserDeactivatingActionEvent;

final class DeactivateUserAction
{
    use ChangesStatus;

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Deactivate a user: the account is closed but kept, and can be
     * reactivated.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Model $user, array $data = [], ?Model $actor = null): Model
    {
        $this->guardTransition($user, UserStatus::Deactivated, $actor);

        $reason = is_string($data['reason'] ?? null) ? $data['reason'] : null;

        UserDeactivatingActionEvent::dispatch($user, $reason);

        DB::transaction(fn () => $this->changeStatus($user, UserStatus::Deactivated, $reason));

        $user = $user->load('rosterProfile');

        UserDeactivatedActionEvent::dispatch($user, $reason);

        return $user;
    }
}
