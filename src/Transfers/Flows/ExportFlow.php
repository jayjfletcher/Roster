<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Transfers\Flows;

use RefactorCircus\Impex\Domains\Flow\Support\Flow;
use RefactorCircus\Roster\Transfers\Flows\Actions\BuildExport;
use RefactorCircus\Roster\Transfers\Flows\Actions\FinishExport;

/**
 * Stream the export to a file (resumably), then mark it ready.
 */
final class ExportFlow extends Flow
{
    /**
     * @return array<string, mixed>
     */
    public function handle(string $transfer): array
    {
        $built = $this->action(BuildExport::class, $transfer)->run();

        return $this->action(FinishExport::class, $transfer, (int) ($built['rows'] ?? 0))->run();
    }
}
