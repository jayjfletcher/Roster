<?php

declare(strict_types=1);

namespace JayI\Roster\Transfers\Flows;

use JayI\Impex\Contracts\BatchSource;
use JayI\Impex\Runtime\BatchChunk;
use JayI\Impex\Runtime\BatchChunkItem;
use JayI\Roster\Models\Transfer;
use JayI\Roster\Transfers\Csv\Reader;
use JayI\Roster\Transfers\Transfers;

/**
 * Feeds an import's CSV into a batch, resuming by byte offset. Each item is
 * keyed by its line, so a retried chunk never applies a row twice.
 */
final class CsvRowsSource implements BatchSource
{
    public function __construct(private readonly string $transfer) {}

    public function chunk(?string $cursor, int $size): BatchChunk
    {
        $transfer = Transfer::query()->withoutReport()->findOrFail($this->transfer);
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
