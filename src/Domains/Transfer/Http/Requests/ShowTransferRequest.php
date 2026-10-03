<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Transfer\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Transfer\Actions\ShowTransferAction;
use JayI\Roster\Domains\Transfer\Resources\TransferResource;

final class ShowTransferRequest extends TransferRequest
{
    public function rules(): array
    {
        return ShowTransferAction::rules();
    }

    public function persist(): JsonResponse
    {
        return TransferResource::make(app(ShowTransferAction::class)->execute($this->transfer()))->withRows($this->integer('rows_page', 1))->response();
    }
}
