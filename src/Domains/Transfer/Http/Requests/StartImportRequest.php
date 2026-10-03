<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Transfer\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Transfer\Actions\StartImportAction;
use JayI\Roster\Domains\Transfer\Resources\TransferResource;

final class StartImportRequest extends StartTransferRequest
{
    protected function fallbackAbility(): string
    {
        return 'roster.users.create';
    }

    public function rules(): array
    {
        return StartImportAction::rules();
    }

    public function persist(): JsonResponse
    {
        $data = $this->validated();
        $data['file'] = $this->file('file');

        return (new TransferResource(app(StartImportAction::class)->execute($data, $this->requester())))->response()->setStatusCode(202);
    }

    private function requester(): Model
    {
        return $this->actor() ?? abort(401);
    }
}
