<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Actions\ConfirmImportAction;
use JayI\Roster\Http\Resources\TransferResource;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class ConfirmImportMcpRequest extends TransferMcpRequest
{
    /**
     * Confirming applies the rows as the confirmer, so it always needs the
     * import's permission - starting the import is not enough.
     */
    protected function self(): ?Model
    {
        return null;
    }

    protected function rules(): array
    {
        return ConfirmImportAction::rules() + ['transfer' => ['required', 'string']];
    }

    protected function handle(array $validated): Response|ResponseFactory
    {
        if (! $this->transfer()->type->isImport()) {
            return Response::error('Not found.');
        }

        return Response::structured(['data' => (new TransferResource(app(ConfirmImportAction::class)->execute($this->transfer(), $this->actor())))->signed()->resolve()]);
    }
}
