<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use JayI\Roster\Actions\Concerns\ChangesStatus;
use JayI\Roster\Enums\UserStatus;
use JayI\Roster\Events\Action\UserSuspendedActionEvent;
use JayI\Roster\Events\Action\UserSuspendingActionEvent;

final class SuspendUserAction
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
     * Suspend a user: a temporary block, lifted by reactivating.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Model $user, array $data = [], ?Model $actor = null): Model
    {
        $this->guardTransition($user, UserStatus::Suspended, $actor);

        $reason = is_string($data['reason'] ?? null) ? $data['reason'] : null;

        UserSuspendingActionEvent::dispatch($user, $reason);

        DB::transaction(fn () => $this->changeStatus($user, UserStatus::Suspended, $reason));

        $user = $user->load('rosterProfile');

        UserSuspendedActionEvent::dispatch($user, $reason);

        return $user;
    }
}
