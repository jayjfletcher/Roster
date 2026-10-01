<?php

declare(strict_types=1);

namespace JayI\Roster\Transfers\Exporters;

use JayI\Roster\Models\Transfer;

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
    public function rows(Transfer $transfer, ?string $after, int $limit): array;
}
