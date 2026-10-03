<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Scim\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Scim\Events\ScimTokensListedActionEvent;
use JayI\Roster\Domains\Scim\Events\ScimTokensListingActionEvent;
use JayI\Roster\Domains\Scim\Models\ScimTokenModel;

final class ListScimTokensAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, ScimTokenModel>
     */
    public function execute(OrganizationModel $organization, array $filters = []): LengthAwarePaginator
    {
        ScimTokensListingActionEvent::dispatch($organization, $filters);

        $perPage = is_numeric($filters['per_page'] ?? null) ? (int) $filters['per_page'] : 25;
        $page = is_numeric($filters['page'] ?? null) ? (int) $filters['page'] : null;

        $tokens = ScimTokenModel::query()
            ->where('organization_id', $organization->getKey())
            ->with(['organization', 'ssoConnection'])
            ->latest()
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);

        ScimTokensListedActionEvent::dispatch($organization, $filters);

        return $tokens;
    }
}
