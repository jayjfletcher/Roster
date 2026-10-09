<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\User\Concerns\ChangesStatus;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Domains\User\Events\UserRejectedActionEvent;
use RefactorCircus\Roster\Domains\User\Events\UserRejectingActionEvent;
use RefactorCircus\Roster\Domains\User\Services\Approvals;
use RefactorCircus\Roster\Support\Users;

final class RejectUserAction
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
     * Turn down an account that is waiting for approval. It's deactivated
     * with the reason, not deleted: the email stays taken, and it can be
     * reactivated later.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Model $user, array $data = [], ?Model $actor = null): Model
    {
        if (app(Users::class)->status($user) !== UserStatus::Pending) {
            throw ValidationException::withMessages(['user' => __('roster::roster.not_pending')]);
        }

        if ($actor !== null && $actor->is($user)) {
            throw ValidationException::withMessages(['user' => __('roster::roster.cannot_change_own_status')]);
        }

        $reason = is_string($data['reason'] ?? null) ? $data['reason'] : null;

        UserRejectingActionEvent::dispatch($user, $reason);

        DB::transaction(fn () => $this->changeStatus($user, UserStatus::Deactivated, $reason));

        $user = $user->load('rosterProfile');

        UserRejectedActionEvent::dispatch($user, $reason);

        app(Approvals::class)->rejected($user, $reason);

        return $user;
    }
}
