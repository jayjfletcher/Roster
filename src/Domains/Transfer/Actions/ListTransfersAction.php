<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Transfer\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use JayI\Roster\Domains\Transfer\Enums\TransferStatus;
use JayI\Roster\Domains\Transfer\Enums\TransferType;
use JayI\Roster\Domains\Transfer\Events\TransfersListedActionEvent;
use JayI\Roster\Domains\Transfer\Events\TransfersListingActionEvent;
use JayI\Roster\Domains\Transfer\Models\TransferModel;
use JayI\Roster\Domains\Transfer\Services\Transfers;
use JayI\Roster\Support\Concerns\ResolvesScopes;

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
     * requester's own (when one is given), plus those of `$organizations`.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<int, int|string>  $organizations
     * @return LengthAwarePaginator<int, TransferModel>
     */
    public function execute(array $filters = [], ?Model $requester = null, array $organizations = []): LengthAwarePaginator
    {
        TransfersListingActionEvent::dispatch($filters);

        $query = TransferModel::query()->with(['organization', 'requester']);
        $organization = $this->organizationFrom($filters['organization'] ?? null);

        if ($organization !== null) {
            $query->where('organization_id', $organization->getKey());
        } elseif ($requester !== null) {
            // Their own, and those of the organizations they may see transfers in.
            $query->where(fn (Builder $builder): Builder => $builder
                ->where('requested_by', $requester->getKey())
                ->when($organizations !== [], fn (Builder $within): Builder => $within->orWhereIn('organization_id', $organizations)));
        }

        foreach (['type', 'status'] as $column) {
            if (is_string($filters[$column] ?? null) && $filters[$column] !== '') {
                $query->where($column, $filters[$column]);
            }
        }

        $perPage = is_numeric($filters['per_page'] ?? null) ? (int) $filters['per_page'] : 25;
        $page = is_numeric($filters['page'] ?? null) ? (int) $filters['page'] : null;

        $transfers = $query->latest()->latest('id')->paginate($perPage, ['*'], 'page', $page);

        // Progress for the whole page in one query rather than one per row.
        app(Transfers::class)->preloadProgress($transfers->getCollection());

        TransfersListedActionEvent::dispatch($filters);

        return $transfers;
    }
}
