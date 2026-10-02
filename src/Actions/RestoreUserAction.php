<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Events\Action\UserRestoredActionEvent;
use JayI\Roster\Events\Action\UserRestoringActionEvent;
use JayI\Roster\Models\Organization;
use JayI\Roster\Support\Users;

final class RestoreUserAction
{
    public function __construct(private readonly Users $users) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    /**
     * Bring back a deleted user, with the profile, memberships and roles they
     * kept while deleted.
     */
    public function execute(Model $user): Model
    {
        if (! $this->users->trashed($user)) {
            throw ValidationException::withMessages(['user' => __('roster::roster.not_deleted')]);
        }

        UserRestoringActionEvent::dispatch($user);

        DB::transaction(function () use ($user): void {
            if (method_exists($user, 'restore')) {
                $user->restore();
            }

            // Their personal organization comes back with them.
            Organization::onlyTrashed()->where('owner_id', $user->getKey())->where('personal', true)->get()->each(fn (Organization $organization): bool => $organization->restore());
        });

        $user = $user->load('rosterProfile');

        UserRestoredActionEvent::dispatch($user);

        return $user;
    }
}
