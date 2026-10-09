<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Transfer\Actions\CancelTransferAction;
use RefactorCircus\Roster\Domains\Transfer\Resources\TransferResource;

final class CancelTransferMcpRequest extends TransferMcpRequest
{
    protected function rules(): array
    {
        return CancelTransferAction::rules() + ['transfer' => ['required', 'string']];
    }

    protected function handle(array $validated): ResponseFactory
    {
        return Response::structured(['data' => (new TransferResource(app(CancelTransferAction::class)->execute($this->transfer())))->signed()->resolve()]);
    }
}
