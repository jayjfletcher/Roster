<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Scim\Http\Requests\IndexScimTokensRequest;
use RefactorCircus\Roster\Domains\Scim\Http\Requests\RevokeScimTokenRequest;
use RefactorCircus\Roster\Domains\Scim\Http\Requests\StoreScimTokenRequest;

final class ScimTokenController
{
    public function index(IndexScimTokensRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function store(StoreScimTokenRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(RevokeScimTokenRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
