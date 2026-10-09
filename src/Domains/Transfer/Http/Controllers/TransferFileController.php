<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Http\Controllers;

use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\Transfer\Services\Transfers;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The target of the short-lived signed links MCP hands out. The signature is
 * the authorization: it was issued to someone allowed to see the export.
 */
final class TransferFileController
{
    public function __invoke(string $transfer, Transfers $transfers): StreamedResponse
    {
        return $transfers->download(TransferModel::query()->whereKey($transfer)->firstOrFail());
    }
}
