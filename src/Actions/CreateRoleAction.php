<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Access\Permissions;
use JayI\Roster\Actions\Concerns\GuardsEscalation;
use JayI\Roster\Actions\Concerns\ResolvesScopes;
use JayI\Roster\Enums\RoleScope;
use JayI\Roster\Events\Action\RoleCreatedActionEvent;
use JayI\Roster\Events\Action\RoleCreatingActionEvent;
use JayI\Roster\Models\Permission;
use JayI\Roster\Models\Role;

final class CreateRoleAction
{
    use GuardsEscalation;
    use ResolvesScopes;

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'alpha_dash', 'max:255'],
            'scope' => ['required', Rule::enum(RoleScope::class)],
            'organization' => ['sometimes', 'nullable', 'string'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::exists('roster_permissions', 'name')],
        ];
    }

    /**
     * Create a role. With `organization` it is that organization's own
     * (organization or team scope only); without, every organization shares it.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?Model $actor = null): Role
    {
        $scope = RoleScope::from((string) $data['scope']);
        $organization = $this->organizationFrom($data['organization'] ?? null);
        $slug = is_string($data['slug'] ?? null) && $data['slug'] !== '' ? $data['slug'] : Str::slug((string) $data['name']);
        /** @var array<int, string> $permissions */
        $permissions = array_values(array_map('strval', (array) ($data['permissions'] ?? [])));

        if ($organization !== null && $scope === RoleScope::Global) {
            throw ValidationException::withMessages(['organization' => __('roster::roster.global_role_shared')]);
        }

        $taken = Role::query()
            ->where('scope', $scope)
            ->where('slug', $slug)
            ->where(fn (Builder $query): Builder => $organization === null ? $query->whereNull('organization_id') : $query->where('organization_id', $organization->getKey()))
            ->exists();

        if ($taken || $slug === '') {
            throw ValidationException::withMessages(['slug' => __('roster::roster.role_slug_taken')]);
        }

        $this->guardEscalation($actor, $permissions, $organization, 'permissions');

        RoleCreatingActionEvent::dispatch($data);

        $role = DB::transaction(function () use ($data, $scope, $organization, $slug, $permissions): Role {
            $role = Role::query()->create([
                'name' => $data['name'],
                'slug' => $slug,
                'scope' => $scope,
                'organization_id' => $organization?->getKey(),
                'description' => $data['description'] ?? null,
            ]);

            $role->permissions()->sync(Permission::query()->whereIn('name', $permissions)->pluck('id'));

            return $role;
        });

        app(Permissions::class)->flush();

        $role->load(['permissions', 'organization']);

        RoleCreatedActionEvent::dispatch($role);

        return $role;
    }
}
