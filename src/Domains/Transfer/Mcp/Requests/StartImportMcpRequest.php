<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Transfer\Actions\StartImportAction;
use RefactorCircus\Roster\Domains\Transfer\Resources\TransferResource;

final class StartImportMcpRequest extends StartTransferMcpRequest
{
    protected function fallbackAbility(): string
    {
        return 'roster.users.create';
    }

    protected function rules(): array
    {
        return array_diff_key(StartImportAction::rules(), ['file' => true]) + ['content' => ['required', 'string', 'max:'.(int) config('roster.transfers.max_bytes', 5242880)]];
    }

    protected function handle(array $validated): Response|ResponseFactory
    {
        $actor = $this->actor();

        if ($actor === null) {
            return Response::error('Unauthorized.');
        }

        return Response::structured(['data' => (new TransferResource(app(StartImportAction::class)->execute($validated, $actor)))->resolve()]);
    }
}
