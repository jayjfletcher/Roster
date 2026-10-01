<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use JayI\Roster\Events\Action\ScimTokensListedActionEvent;
use JayI\Roster\Events\Action\ScimTokensListingActionEvent;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\ScimToken;

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
     * @return LengthAwarePaginator<int, ScimToken>
     */
    public function execute(Organization $organization, array $filters = []): LengthAwarePaginator
    {
        ScimTokensListingActionEvent::dispatch($organization, $filters);

        $perPage = is_numeric($filters['per_page'] ?? null) ? (int) $filters['per_page'] : 25;
        $page = is_numeric($filters['page'] ?? null) ? (int) $filters['page'] : null;

        $tokens = ScimToken::query()
            ->where('organization_id', $organization->getKey())
            ->with(['organization', 'ssoConnection'])
            ->latest()
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);

        ScimTokensListedActionEvent::dispatch($organization, $filters);

        return $tokens;
    }
}
