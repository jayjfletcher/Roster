<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\ConfirmImportAction;
use JayI\Roster\Http\Resources\TransferResource;

/**
 * Confirming applies the rows as the confirmer, so it always needs the
 * import's permission - starting the import is not enough.
 */
final class ConfirmImportRequest extends TransferRequest
{
    protected function self(): ?Model
    {
        return null;
    }

    public function rules(): array
    {
        return ConfirmImportAction::rules();
    }

    public function persist(): JsonResponse
    {
        abort_unless($this->transfer()->type->isImport(), 404);

        return (new TransferResource(app(ConfirmImportAction::class)->execute($this->transfer(), $this->actor())))->response();
    }
}
