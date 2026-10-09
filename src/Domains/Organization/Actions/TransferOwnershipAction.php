<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Events\OwnershipTransferredActionEvent;
use RefactorCircus\Roster\Domains\Organization\Events\OwnershipTransferringActionEvent;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Support\Users;

final class TransferOwnershipAction
{
    public function __construct(private readonly Users $users) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'user' => ['required'],
        ];
    }

    /**
     * Hand an organization to another of its members.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(OrganizationModel $organization, array $data): OrganizationModel
    {
        $user = $this->users->findOrFail($data['user'] ?? null);

        $this->guard($organization, $user);

        OwnershipTransferringActionEvent::dispatch($organization, $user);

        DB::transaction(fn () => $organization->update(['owner_id' => $user->getKey()]));

        $organization = $organization->refresh()->load(['domains', 'links'])->loadCount(['memberships', 'teams']);

        OwnershipTransferredActionEvent::dispatch($organization, $user);

        return $organization;
    }

    private function guard(OrganizationModel $organization, Model $user): void
    {
        if ($organization->personal) {
            throw ValidationException::withMessages(['organization' => __('roster::roster.cannot_transfer_personal')]);
        }

        if ($organization->isOwnedBy($user)) {
            throw ValidationException::withMessages(['user' => __('roster::roster.already_owner')]);
        }

        if ($organization->membershipFor($user) === null) {
            throw ValidationException::withMessages(['user' => __('roster::roster.not_a_member')]);
        }
    }
}
