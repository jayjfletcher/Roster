<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\CreateSsoConnectionAction;
use JayI\Roster\Http\Resources\SsoConnectionResource;
use JayI\Roster\Models\Organization;

final class StoreSsoConnectionRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.sso.manage';
    }

    protected function scope(): Organization
    {
        return $this->organization();
    }

    public function rules(): array
    {
        return CreateSsoConnectionAction::rules();
    }

    public function persist(): JsonResponse
    {
        $connection = app(CreateSsoConnectionAction::class)->execute($this->organization(), $this->validated());

        return (new SsoConnectionResource($connection))->response()->setStatusCode(201);
    }
}
