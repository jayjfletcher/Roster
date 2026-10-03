<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Sso\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Organization\Http\Requests\OrganizationRequest;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Sso\Actions\CreateSsoConnectionAction;
use JayI\Roster\Domains\Sso\Resources\SsoConnectionResource;

final class StoreSsoConnectionRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.sso.manage';
    }

    protected function scope(): OrganizationModel
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
