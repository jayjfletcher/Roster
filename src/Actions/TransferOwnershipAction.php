<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Events\Action\OwnershipTransferredActionEvent;
use JayI\Roster\Events\Action\OwnershipTransferringActionEvent;
use JayI\Roster\Models\Organization;
use JayI\Roster\Support\Users;

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
    public function execute(Organization $organization, array $data): Organization
    {
        $user = $this->users->findOrFail($data['user'] ?? null);

        $this->guard($organization, $user);

        OwnershipTransferringActionEvent::dispatch($organization, $user);

        DB::transaction(fn () => $organization->update(['owner_id' => $user->getKey()]));

        $organization = $organization->refresh()->load(['domains', 'links'])->loadCount(['memberships', 'teams']);

        OwnershipTransferredActionEvent::dispatch($organization, $user);

        return $organization;
    }

    private function guard(Organization $organization, Model $user): void
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
