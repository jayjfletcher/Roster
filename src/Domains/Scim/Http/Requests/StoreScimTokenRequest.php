<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Http\Requests\OrganizationRequest;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Scim\Actions\CreateScimTokenAction;
use RefactorCircus\Roster\Domains\Scim\Resources\ScimTokenResource;

final class StoreScimTokenRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.scim.manage';
    }

    protected function scope(): OrganizationModel
    {
        return $this->organization();
    }

    public function rules(): array
    {
        return CreateScimTokenAction::rules();
    }

    /**
     * The token is in the response once.
     */
    public function persist(): JsonResponse
    {
        $issued = app(CreateScimTokenAction::class)->execute($this->organization(), $this->validated(), $this->actor());

        return (new ScimTokenResource($issued->token))->additional(['token' => $issued->plain])->response()->setStatusCode(201);
    }
}
