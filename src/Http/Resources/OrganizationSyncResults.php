<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Resources;

use JayI\Roster\Support\OrganizationSyncResult;

/**
 * A bulk sync's per-record results, in the order the records came:
 * `{source, external_id, outcome, organization, errors}`.
 */
final class OrganizationSyncResults
{
    /**
     * @param  array<int, array{source: mixed, external_id: mixed, outcome: string, result?: OrganizationSyncResult, errors?: array<string, array<int, string>>}>  $results
     * @return array<int, array<string, mixed>>
     */
    public static function present(array $results): array
    {
        return array_map(fn (array $entry): array => [
            'source' => $entry['source'],
            'external_id' => $entry['external_id'],
            'outcome' => $entry['outcome'],
            'organization' => isset($entry['result']) ? $entry['result']->organization->slug : null,
            'errors' => $entry['errors'] ?? null,
        ], $results);
    }
}
