<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Audit\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use JayI\Roster\Domains\Audit\Events\AuditEntriesListedActionEvent;
use JayI\Roster\Domains\Audit\Events\AuditEntriesListingActionEvent;
use JayI\Roster\Domains\Audit\Models\AuditEntryModel;
use JayI\Roster\Domains\Invitation\Models\InvitationModel;
use JayI\Roster\Domains\Organization\Models\MembershipModel;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Permission\Models\PermissionModel;
use JayI\Roster\Domains\Role\Models\RoleAssignmentModel;
use JayI\Roster\Domains\Role\Models\RoleModel;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Support\Concerns\ResolvesScopes;
use JayI\Roster\Support\Users;

final class ListAuditEntriesAction
{
    use ResolvesScopes;

    public function __construct(private readonly Users $users) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'organization' => ['sometimes', 'nullable', 'string'],
            'user' => ['sometimes', 'nullable'],
            'source' => ['sometimes', 'nullable', Rule::in([AuditEntryModel::SOURCE_ROSTER, AuditEntryModel::SOURCE_APP])],
            'action' => ['sometimes', 'nullable', 'string', 'max:255'],
            'subject_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'since' => ['sometimes', 'nullable', 'date'],
            'until' => ['sometimes', 'nullable', 'date'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * Newest first. `user` matches entries about the user or made by them;
     * `action` matches exactly, or a prefix ending in `.` (e.g. `user.`).
     *
     * @param  array<string, mixed>  $filters
     * @param  array<int, int|string>|null  $organizations  Only these organizations; null for no limit.
     * @return LengthAwarePaginator<int, AuditEntryModel>
     */
    public function execute(array $filters = [], ?array $organizations = null): LengthAwarePaginator
    {
        AuditEntriesListingActionEvent::dispatch($filters);

        $query = AuditEntryModel::query()->with(['actor', 'organization']);

        $organization = $this->organizationFrom($filters['organization'] ?? null);

        if ($organization !== null) {
            $query->where('organization_id', $organization->getKey());
        }

        if ($organizations !== null) {
            $query->whereIn('organization_id', $organizations);
        }

        if (($filters['user'] ?? null) !== null) {
            $query->about($this->users->resolve($filters['user']));
        }

        foreach (['source', 'subject_type'] as $column) {
            if (is_string($filters[$column] ?? null) && $filters[$column] !== '') {
                $query->where($column, $column === 'subject_type' ? $this->morphType($filters[$column]) : $filters[$column]);
            }
        }

        $action = $filters['action'] ?? null;

        if (is_string($action) && $action !== '') {
            str_ends_with($action, '.') ? $query->where('action', 'like', $action.'%') : $query->where('action', $action);
        }

        if (is_string($filters['since'] ?? null)) {
            $query->where('created_at', '>=', Carbon::parse($filters['since']));
        }

        if (is_string($filters['until'] ?? null)) {
            $query->where('created_at', '<=', Carbon::parse($filters['until']));
        }

        $perPage = is_numeric($filters['per_page'] ?? null) ? (int) $filters['per_page'] : 25;
        $page = is_numeric($filters['page'] ?? null) ? (int) $filters['page'] : null;

        $entries = $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

        AuditEntriesListedActionEvent::dispatch($filters);

        return $entries;
    }

    /**
     * Accept short names (`user`, `organization`, …) as well as morph types.
     */
    private function morphType(string $type): string
    {
        return match ($type) {
            'user' => $this->users->newModel()->getMorphClass(),
            'organization' => (new OrganizationModel)->getMorphClass(),
            'team' => (new TeamModel)->getMorphClass(),
            'role' => (new RoleModel)->getMorphClass(),
            'permission' => (new PermissionModel)->getMorphClass(),
            'invitation' => (new InvitationModel)->getMorphClass(),
            'membership' => (new MembershipModel)->getMorphClass(),
            'assignment' => (new RoleAssignmentModel)->getMorphClass(),
            default => $type,
        };
    }
}
