<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use JayI\Roster\Events\Action\PermissionsListedActionEvent;
use JayI\Roster\Events\Action\PermissionsListingActionEvent;
use JayI\Roster\Models\Permission;

final class ListPermissionsAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:200'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Permission>
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        PermissionsListingActionEvent::dispatch($filters);

        $query = Permission::query();
        $search = $filters['search'] ?? null;

        if (is_string($search) && $search !== '') {
            $query->where('name', 'like', '%'.$search.'%');
        }

        $perPage = is_numeric($filters['per_page'] ?? null) ? (int) $filters['per_page'] : 50;
        $page = is_numeric($filters['page'] ?? null) ? (int) $filters['page'] : null;

        $permissions = $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page);

        PermissionsListedActionEvent::dispatch($filters);

        return $permissions;
    }
}
