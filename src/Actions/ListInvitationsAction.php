<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use JayI\Roster\Enums\InvitationStatus;
use JayI\Roster\Events\Action\InvitationsListedActionEvent;
use JayI\Roster\Events\Action\InvitationsListingActionEvent;
use JayI\Roster\Models\Invitation;
use JayI\Roster\Models\Organization;

final class ListInvitationsAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', Rule::enum(InvitationStatus::class)],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * Newest first.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Invitation>
     */
    public function execute(Organization $organization, array $filters = []): LengthAwarePaginator
    {
        InvitationsListingActionEvent::dispatch($organization, $filters);

        $query = $organization->invitations()->with('organization');

        $status = isset($filters['status']) ? InvitationStatus::tryFrom((string) $filters['status']) : null;

        if ($status !== null) {
            $query->withStatus($status);
        }

        $perPage = is_numeric($filters['per_page'] ?? null) ? (int) $filters['per_page'] : 15;
        $page = is_numeric($filters['page'] ?? null) ? (int) $filters['page'] : null;

        $invitations = $query->latest()->latest('id')->paginate($perPage, ['*'], 'page', $page);

        InvitationsListedActionEvent::dispatch($organization, $filters);

        return $invitations;
    }
}
