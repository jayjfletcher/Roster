<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Controllers;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Http\Requests\IndexScimTokensRequest;
use JayI\Roster\Http\Requests\RevokeScimTokenRequest;
use JayI\Roster\Http\Requests\StoreScimTokenRequest;

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
