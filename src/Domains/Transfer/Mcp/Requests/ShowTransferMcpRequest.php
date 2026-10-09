<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Transfer\Actions\ShowTransferAction;
use RefactorCircus\Roster\Domains\Transfer\Resources\TransferResource;

final class ShowTransferMcpRequest extends TransferMcpRequest
{
    protected function rules(): array
    {
        return ShowTransferAction::rules() + ['transfer' => ['required', 'string']];
    }

    protected function handle(array $validated): ResponseFactory
    {
        return Response::structured(['data' => TransferResource::make(app(ShowTransferAction::class)->execute($this->transfer()))->withRows((int) ($validated['rows_page'] ?? 1))->signed()->resolve()]);
    }
}
