<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Transfer\Mcp\Requests;

use JayI\Roster\Domains\Transfer\Actions\CancelTransferAction;
use JayI\Roster\Domains\Transfer\Resources\TransferResource;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

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
