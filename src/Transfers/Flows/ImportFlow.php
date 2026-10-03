<?php

declare(strict_types=1);

namespace JayI\Roster\Transfers\Flows;

use Illuminate\Support\Carbon;
use JayI\Impex\Domains\Flow\Support\Flow;
use JayI\Roster\Transfers\Flows\Actions\ApplyImportRow;
use JayI\Roster\Transfers\Flows\Actions\CloseTransfer;
use JayI\Roster\Transfers\Flows\Actions\FinishImport;
use JayI\Roster\Transfers\Flows\Actions\ValidateImport;

/**
 * Validate every row, wait for a person to confirm the preview, then apply
 * the rows as one batch and record each row's outcome.
 */
final class ImportFlow extends Flow
{
    /**
     * @return array<string, mixed>
     */
    public function handle(string $transfer): array
    {
        $preview = $this->action(ValidateImport::class, $transfer)->run();

        if (($preview['valid'] ?? false) !== true) {
            return ['status' => 'failed'];
        }

        // The deadline comes from the recorded step, so replays agree on it.
        $decision = $this->signal('confirm')
            ->timeoutAfter(Carbon::parse((string) $preview['expires_at']))
            ->default(['confirmed' => false, 'reason' => 'expired'])
            ->wait();

        if (! is_array($decision) || ($decision['confirmed'] ?? false) !== true) {
            $reason = is_array($decision) && is_string($decision['reason'] ?? null) ? $decision['reason'] : 'cancelled';

            return $this->action(CloseTransfer::class, $transfer, $reason)->run();
        }

        $summary = $this->batch(CsvRowsSource::class, $transfer)
            ->using(ApplyImportRow::class)
            ->chunk((int) config('roster.transfers.chunk', 200))
            ->allowFailures(1.0)
            ->tries(2)
            ->run();

        return $this->action(FinishImport::class, $transfer, (string) $summary['batch_id'])->run();
    }
}
