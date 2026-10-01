<?php

declare(strict_types=1);

namespace JayI\Roster\Transfers\Flows\Actions;

use JayI\Impex\Flows\ResumableAction;
use JayI\Roster\Models\Transfer;
use JayI\Roster\Transfers\Csv\Writer;
use JayI\Roster\Transfers\Transfers;

/**
 * Write the export in chunks, yielding when the step's time runs short and
 * resuming from the last row written.
 */
final class BuildExport extends ResumableAction
{
    public function execute(string $transfer): mixed
    {
        $model = Transfer::query()->findOrFail($transfer);
        $transfers = app(Transfers::class);
        $exporter = $transfers->exporter($model);
        $disk = $transfers->disk();
        $path = 'roster/transfers/'.$model->id.'/export.csv';
        $state = $this->cursor();
        [$after, $written] = $state === null ? [null, 0] : [explode('|', $state, 2)[1] ?: null, (int) explode('|', $state, 2)[0]];

        if ($state === null) {
            $disk->put($path, '');
            $model->update(['output_path' => $path]);
        }

        $writer = new Writer($disk->path($path), append: true);

        if ($state === null) {
            $writer->write($exporter->headers());
        }

        try {
            while (true) {
                $rows = $exporter->rows($model, $after, (int) config('roster.transfers.chunk', 200));

                if ($rows === []) {
                    return ['rows' => $written];
                }

                foreach ($rows as $row) {
                    $writer->write($row['cells']);
                    $after = $row['cursor'];
                    $written++;
                }

                if ($this->shouldYield()) {
                    return $this->yieldTo($written.'|'.$after);
                }
            }
        } finally {
            $writer->close();
        }
    }
}
