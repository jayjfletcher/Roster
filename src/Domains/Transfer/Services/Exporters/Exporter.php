<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Services\Exporters;

use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;

interface Exporter
{
    /**
     * @return array<int, string>
     */
    public function headers(): array;

    /**
     * The next rows after `$after` (the cursor of the last row written).
     *
     * @return array<int, array{cursor: string, cells: array<int, mixed>}>
     */
    public function rows(TransferModel $transfer, ?string $after, int $limit): array;
}
