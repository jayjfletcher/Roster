<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Transfer\Mcp\Requests;

use JayI\Roster\Domains\Transfer\Actions\StartExportAction;
use JayI\Roster\Domains\Transfer\Resources\TransferResource;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class StartExportMcpRequest extends StartTransferMcpRequest
{
    protected function fallbackAbility(): string
    {
        return 'roster.users.view';
    }

    protected function rules(): array
    {
        return StartExportAction::rules();
    }

    protected function handle(array $validated): Response|ResponseFactory
    {
        $actor = $this->actor();

        if ($actor === null) {
            return Response::error('Unauthorized.');
        }

        return Response::structured(['data' => (new TransferResource(app(StartExportAction::class)->execute($validated, $actor)))->resolve()]);
    }
}
