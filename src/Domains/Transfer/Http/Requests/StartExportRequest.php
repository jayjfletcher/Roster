<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Transfer\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Transfer\Actions\StartExportAction;
use JayI\Roster\Domains\Transfer\Resources\TransferResource;

final class StartExportRequest extends StartTransferRequest
{
    protected function fallbackAbility(): string
    {
        return 'roster.users.view';
    }

    public function rules(): array
    {
        return StartExportAction::rules();
    }

    public function persist(): JsonResponse
    {
        return (new TransferResource(app(StartExportAction::class)->execute($this->validated(), $this->requester())))->response()->setStatusCode(202);
    }

    private function requester(): Model
    {
        return $this->actor() ?? abort(401);
    }
}
