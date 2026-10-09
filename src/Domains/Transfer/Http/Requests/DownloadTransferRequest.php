<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Http\Requests;

use RefactorCircus\Roster\Domains\Transfer\Services\Transfers;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DownloadTransferRequest extends TransferRequest
{
    public function persist(): StreamedResponse
    {
        return app(Transfers::class)->download($this->transfer());
    }
}
