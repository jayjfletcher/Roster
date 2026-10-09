<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Transfers\Flows;

use RefactorCircus\Impex\Domains\Batch\Contracts\BatchSource;
use RefactorCircus\Impex\Domains\Batch\Data\BatchChunk;
use RefactorCircus\Impex\Domains\Batch\Data\BatchChunkItem;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\Transfer\Services\Transfers;
use RefactorCircus\Roster\Domains\Transfer\Support\Csv\Reader;

/**
 * Feeds an import's CSV into a batch, resuming by byte offset. Each item is
 * keyed by its line, so a retried chunk never applies a row twice.
 */
final class CsvRowsSource implements BatchSource
{
    public function __construct(private readonly string $transfer) {}

    public function chunk(?string $cursor, int $size): BatchChunk
    {
        $transfer = TransferModel::query()->withoutReport()->findOrFail($this->transfer);
        [$offset, $line] = $cursor === null ? [0, 2] : array_map('intval', explode(':', $cursor, 2));

        $reader = new Reader(app(Transfers::class)->disk(), (string) $transfer->input_path, $transfer->type->columns());
        $items = [];
        $next = null;

        foreach ($reader->rows($offset, $line) as $row) {
            $items[] = new BatchChunkItem('line-'.$row['line'], [
                'transfer' => $transfer->id,
                'line' => $row['line'],
                'values' => $row['values'],
            ]);

            $next = $row['offset'].':'.($row['line'] + 1);

            if (count($items) >= $size) {
                return BatchChunk::of($items, $next);
            }
        }

        return BatchChunk::last($items);
    }
}
