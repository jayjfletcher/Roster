<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use JayI\Roster\Transfers\Transfers;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DownloadTransferRequest extends TransferRequest
{
    public function persist(): StreamedResponse
    {
        return app(Transfers::class)->download($this->transfer());
    }
}
