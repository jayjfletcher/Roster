<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Transfer\Services\Exporters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use JayI\Roster\Domains\Audit\Models\AuditEntryModel;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Transfer\Models\TransferModel;
use JayI\Roster\Support\Users;

/**
 * The audit log, filtered as `ListAuditEntriesAction` filters it. Who may
 * see what was decided when the export started (organization or global).
 */
final class AuditExporter implements Exporter
{
    public function __construct(private readonly Users $users) {}

    public function headers(): array
    {
        return ['id', 'created_at', 'source', 'action', 'actor', 'subject_type', 'subject_id', 'subject', 'organization', 'surface', 'changes', 'context', 'hash'];
    }

    public function rows(TransferModel $transfer, ?string $after, int $limit): array
    {
        $filters = (array) $transfer->filters;
        $query = AuditEntryModel::query()->with(['actor', 'organization']);

        if ($transfer->organization_id !== null) {
            $query->where('organization_id', $transfer->organization_id);
        }

        foreach (['source', 'action'] as $column) {
            if (is_string($filters[$column] ?? null) && $filters[$column] !== '') {
                str_ends_with((string) $filters[$column], '.') && $column === 'action'
                    ? $query->where('action', 'like', $filters[$column].'%')
                    : $query->where($column, $filters[$column]);
            }
        }

        if (is_string($filters['since'] ?? null)) {
            $query->where('created_at', '>=', Carbon::parse($filters['since']));
        }

        if (is_string($filters['until'] ?? null)) {
            $query->where('created_at', '<=', Carbon::parse($filters['until']));
        }

        return $query
            ->when($after !== null, fn (Builder $builder): Builder => $builder->where('id', '>', (int) $after))
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(fn (AuditEntryModel $entry): array => [
                'cursor' => (string) $entry->id,
                'cells' => [
                    $entry->id,
                    $entry->created_at->toIso8601String(),
                    $entry->source,
                    $entry->action,
                    $entry->actor instanceof Model ? $this->users->email($entry->actor) : null,
                    $entry->subject_type,
                    $entry->subject_id,
                    $entry->subject_label,
                    $entry->organization instanceof OrganizationModel ? $entry->organization->slug : null,
                    $entry->surface,
                    $entry->changes ?? [],
                    $entry->context ?? [],
                    $entry->hash,
                ],
            ])
            ->all();
    }
}
