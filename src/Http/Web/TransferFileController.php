<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Web;

use JayI\Roster\Models\Transfer;
use JayI\Roster\Transfers\Transfers;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The target of the short-lived signed links MCP hands out. The signature is
 * the authorization: it was issued to someone allowed to see the export.
 */
final class TransferFileController
{
    public function __invoke(string $transfer, Transfers $transfers): StreamedResponse
    {
        return $transfers->download(Transfer::query()->whereKey($transfer)->firstOrFail());
    }
}
