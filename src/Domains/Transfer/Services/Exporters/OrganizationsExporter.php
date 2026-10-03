<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Transfer\Services\Exporters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Domains\Organization\Models\OrganizationLinkModel;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Transfer\Models\TransferModel;
use JayI\Roster\Support\Users;

/**
 * Organizations in the `import_organizations` columns, so an edited export
 * imports straight back: one row per external record, or one row with no
 * source for an organization that isn't linked. `external_source` limits it
 * to one system's records.
 */
final class OrganizationsExporter implements Exporter
{
    public function __construct(private readonly Users $users) {}

    public function headers(): array
    {
        return ['source', 'external_id', 'name', 'account_number', 'slug', 'domains', 'owner'];
    }

    public function rows(TransferModel $transfer, ?string $after, int $limit): array
    {
        $source = $transfer->filters['external_source'] ?? null;
        $source = is_string($source) && $source !== '' ? $source : null;

        $organizations = OrganizationModel::query()
            ->with(['domains', 'links', 'owner'])
            ->when($source !== null, fn (Builder $query): Builder => $query->whereHas('links', fn (Builder $links): Builder => $links->where('source', $source)))
            ->when($after !== null, fn (Builder $query): Builder => $query->where('id', '>', $after))
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $rows = [];

        foreach ($organizations as $organization) {
            $links = $organization->links->sortBy('source')->filter(fn (OrganizationLinkModel $link): bool => $source === null || $link->source === $source);

            foreach ($links->isEmpty() ? [null] : $links as $link) {
                $owner = $organization->owner;

                $rows[] = [
                    'cursor' => $organization->id,
                    'cells' => [
                        $link?->source,
                        $link?->external_id,
                        $organization->name,
                        $link?->account_number,
                        $organization->slug,
                        $organization->domains->pluck('domain')->sort()->implode(';'),
                        $owner instanceof Model ? $this->users->email($owner) : null,
                    ],
                ];
            }
        }

        return $rows;
    }
}
