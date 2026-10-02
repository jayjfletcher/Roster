<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Validation\ValidationException;
use JayI\Roster\Events\Action\OrganizationsSyncedActionEvent;
use JayI\Roster\Events\Action\OrganizationsSyncingActionEvent;
use JayI\Roster\Support\OrganizationSyncResult;

final class SyncOrganizationsAction
{
    public const string ERROR = 'error';

    /**
     * Records are checked one by one in `execute()`, so one bad record
     * doesn't reject the batch.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'records' => ['required', 'array', 'list', 'min:1', 'max:'.(int) config('roster.organizations.sync_batch', 500)],
            'records.*' => ['array'],
        ];
    }

    /**
     * Sync each record with SyncOrganizationAction, each in its own
     * transaction. Results come back in the same order:
     * `{source, external_id, outcome, organization?, errors?}`.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array{source: mixed, external_id: mixed, outcome: string, result?: OrganizationSyncResult, errors?: array<string, array<int, string>>}>
     */
    public function execute(array $data): array
    {
        /** @var array<int, array<string, mixed>> $records */
        $records = array_values((array) ($data['records'] ?? []));

        OrganizationsSyncingActionEvent::dispatch(count($records));

        $results = [];

        foreach ($records as $record) {
            $entry = ['source' => $record['source'] ?? null, 'external_id' => $record['external_id'] ?? null];

            try {
                $result = app(SyncOrganizationAction::class)->execute($record);
                $results[] = $entry + ['outcome' => $result->outcome, 'result' => $result];
            } catch (ValidationException $exception) {
                $results[] = $entry + ['outcome' => self::ERROR, 'errors' => $exception->errors()];
            }
        }

        $summary = array_count_values(array_column($results, 'outcome'));

        OrganizationsSyncedActionEvent::dispatch($summary);

        return $results;
    }
}
