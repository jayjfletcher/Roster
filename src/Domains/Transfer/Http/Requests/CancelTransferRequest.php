<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Transfer\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Transfer\Actions\CancelTransferAction;
use JayI\Roster\Domains\Transfer\Resources\TransferResource;

final class CancelTransferRequest extends TransferRequest
{
    public function rules(): array
    {
        return CancelTransferAction::rules();
    }

    public function persist(): JsonResponse
    {
        return (new TransferResource(app(CancelTransferAction::class)->execute($this->transfer())))->response();
    }
}
