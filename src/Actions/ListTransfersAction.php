<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use JayI\Roster\Actions\Concerns\ResolvesScopes;
use JayI\Roster\Enums\TransferStatus;
use JayI\Roster\Enums\TransferType;
use JayI\Roster\Events\Action\TransfersListedActionEvent;
use JayI\Roster\Events\Action\TransfersListingActionEvent;
use JayI\Roster\Models\Transfer;

final class ListTransfersAction
{
    use ResolvesScopes;

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'organization' => ['sometimes', 'nullable', 'string'],
            'type' => ['sometimes', 'nullable', Rule::enum(TransferType::class)],
            'status' => ['sometimes', 'nullable', Rule::enum(TransferStatus::class)],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * Newest first. With an organization, its transfers; otherwise only the
     * requester's own (when one is given).
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Transfer>
     */
    public function execute(array $filters = [], ?Model $requester = null): LengthAwarePaginator
    {
        TransfersListingActionEvent::dispatch($filters);

        $query = Transfer::query()->with(['organization', 'requester']);
        $organization = $this->organizationFrom($filters['organization'] ?? null);

        if ($organization !== null) {
            $query->where('organization_id', $organization->getKey());
        } elseif ($requester !== null) {
            $query->where('requested_by', $requester->getKey());
        }

        foreach (['type', 'status'] as $column) {
            if (is_string($filters[$column] ?? null) && $filters[$column] !== '') {
                $query->where($column, $filters[$column]);
            }
        }

        $perPage = is_numeric($filters['per_page'] ?? null) ? (int) $filters['per_page'] : 25;
        $page = is_numeric($filters['page'] ?? null) ? (int) $filters['page'] : null;

        $transfers = $query->latest()->latest('id')->paginate($perPage, ['*'], 'page', $page);

        TransfersListedActionEvent::dispatch($filters);

        return $transfers;
    }
}
