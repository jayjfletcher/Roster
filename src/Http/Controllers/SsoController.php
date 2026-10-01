<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use JayI\Roster\Http\Requests\DestroySsoConnectionRequest;
use JayI\Roster\Http\Requests\DestroySsoIdentityRequest;
use JayI\Roster\Http\Requests\IndexSsoConnectionsRequest;
use JayI\Roster\Http\Requests\IndexUserSsoIdentitiesRequest;
use JayI\Roster\Http\Requests\ShowSsoConnectionRequest;
use JayI\Roster\Http\Requests\StoreSsoConnectionRequest;
use JayI\Roster\Http\Requests\UpdateSsoConnectionRequest;

final class SsoController
{
    public function index(IndexSsoConnectionsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function store(StoreSsoConnectionRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function show(ShowSsoConnectionRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function update(UpdateSsoConnectionRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(DestroySsoConnectionRequest $request): Response
    {
        return $request->persist();
    }

    public function identities(IndexUserSsoIdentitiesRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function unlink(DestroySsoIdentityRequest $request): Response
    {
        return $request->persist();
    }
}
