<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\CreateScimTokenAction;
use JayI\Roster\Http\Resources\ScimTokenResource;
use JayI\Roster\Models\Organization;

final class StoreScimTokenRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.scim.manage';
    }

    protected function scope(): Organization
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
