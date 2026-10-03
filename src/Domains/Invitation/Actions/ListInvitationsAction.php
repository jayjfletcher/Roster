<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Invitation\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use JayI\Roster\Domains\Invitation\Enums\InvitationStatus;
use JayI\Roster\Domains\Invitation\Events\InvitationsListedActionEvent;
use JayI\Roster\Domains\Invitation\Events\InvitationsListingActionEvent;
use JayI\Roster\Domains\Invitation\Models\InvitationModel;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Team\Models\TeamModel;

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
     * @return LengthAwarePaginator<int, InvitationModel>
     */
    public function execute(OrganizationModel $organization, array $filters = []): LengthAwarePaginator
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

        // The teams invitations name, for the whole page in one query.
        $teams = TeamModel::query()
            ->whereIn('id', $invitations->getCollection()->flatMap(fn (InvitationModel $invitation): array => (array) $invitation->teams)->unique()->all())
            ->get(['id', 'slug'])
            ->keyBy('id');

        $invitations->getCollection()->each(fn (InvitationModel $invitation) => $invitation->setRelation(
            'teamModels',
            $teams->only((array) $invitation->teams)->sortBy('slug')->values(),
        ));

        InvitationsListedActionEvent::dispatch($organization, $filters);

        return $invitations;
    }
}
