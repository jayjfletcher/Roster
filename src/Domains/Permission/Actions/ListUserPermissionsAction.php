<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Actions;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\Permission\Events\UserPermissionsListedActionEvent;
use RefactorCircus\Roster\Domains\Permission\Events\UserPermissionsListingActionEvent;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Support\Concerns\ResolvesScopes;

final class ListUserPermissionsAction
{
    use ResolvesScopes;

    public function __construct(private readonly Permissions $permissions) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'organization' => ['sometimes', 'nullable', 'string'],
            'team' => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * The permissions a user effectively holds: globally, or in an
     * organization or one of its teams.
     *
     * @param  array<string, mixed>  $filters
     * @return array{super_admin: bool, permissions: array<int, string>}
     */
    public function execute(Model $user, array $filters = []): array
    {
        UserPermissionsListingActionEvent::dispatch($user, $filters);

        $organization = $this->organizationFrom($filters['organization'] ?? null);
        $team = $this->teamFrom($organization, $filters['team'] ?? null);

        $result = [
            'super_admin' => $this->permissions->isSuperAdmin($user),
            'permissions' => $this->permissions->for($user, $team ?? $organization),
        ];

        UserPermissionsListedActionEvent::dispatch($user, $filters);

        return $result;
    }
}
