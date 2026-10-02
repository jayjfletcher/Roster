<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Actions\Concerns\ChangesStatus;
use JayI\Roster\Enums\UserStatus;
use JayI\Roster\Events\Action\UserApprovedActionEvent;
use JayI\Roster\Events\Action\UserApprovingActionEvent;
use JayI\Roster\Support\Approvals;
use JayI\Roster\Support\Users;

final class ApproveUserAction
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
     * Accept an account that is waiting for approval: it becomes active.
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

        UserApprovingActionEvent::dispatch($user);

        DB::transaction(fn () => $this->changeStatus($user, UserStatus::Active, null));

        $user = $user->load('rosterProfile');

        UserApprovedActionEvent::dispatch($user);

        app(Approvals::class)->approved($user);

        return $user;
    }
}
