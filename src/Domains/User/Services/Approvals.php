<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use JayI\Roster\Domains\Role\Models\RoleAssignmentModel;
use JayI\Roster\Domains\User\Notifications\UserApprovedNotification;
use JayI\Roster\Domains\User\Notifications\UserAwaitingApprovalNotification;
use JayI\Roster\Domains\User\Notifications\UserRejectedNotification;
use JayI\Roster\Support\Users;

/**
 * Who approves new accounts, and the emails around approval. Each email is
 * switched by `roster.users.approvals.*`.
 */
final class Approvals
{
    public function __construct(
        private readonly Users $users,
        private readonly Repository $config,
    ) {}

    /**
     * Holders of `roster.users.approve` across the whole site: a global role
     * granting it or a super role, plus verified configured super-admins.
     *
     * @return Collection<int, Model>
     */
    public function approvers(): Collection
    {
        $ids = RoleAssignmentModel::query()
            ->whereNull('organization_id')
            ->whereNull('team_id')
            ->whereHas('role', fn (Builder $role): Builder => $role
                ->where('super', true)
                ->orWhereHas('permissions', fn (Builder $permission): Builder => $permission->where('name', 'roster.users.approve')))
            ->pluck('user_id');

        $approvers = $this->users->query()->whereKey($ids->all())->get();

        foreach ((array) $this->config->get('roster.super_admins', []) as $email) {
            $user = is_string($email) ? $this->users->findByEmail($email) : null;

            if ($user !== null && $this->users->emailVerified($user) && ! $approvers->contains($user)) {
                $approvers->push($user);
            }
        }

        return $approvers;
    }

    /**
     * Someone is now waiting for approval: tell the approvers.
     */
    public function pending(Model $user): void
    {
        if ($this->config->get('roster.users.approvals.notify_approvers') !== true) {
            return;
        }

        foreach ($this->approvers() as $approver) {
            $email = $this->users->email($approver);

            if ($email !== null && ! $approver->is($user)) {
                Notification::route('mail', $email)->notify(new UserAwaitingApprovalNotification($user));
            }
        }
    }

    public function approved(Model $user): void
    {
        $this->tell($user, new UserApprovedNotification);
    }

    public function rejected(Model $user, ?string $reason): void
    {
        $this->tell($user, new UserRejectedNotification($reason));
    }

    private function tell(Model $user, UserApprovedNotification|UserRejectedNotification $notification): void
    {
        $email = $this->users->email($user);

        if ($email !== null && $this->config->get('roster.users.approvals.notify_user') === true) {
            Notification::route('mail', $email)->notify($notification);
        }
    }
}
