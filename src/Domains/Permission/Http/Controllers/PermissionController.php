<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Permission\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use JayI\Roster\Domains\Permission\Http\Requests\DestroyPermissionRequest;
use JayI\Roster\Domains\Permission\Http\Requests\IndexPermissionsRequest;
use JayI\Roster\Domains\Permission\Http\Requests\StorePermissionRequest;
use JayI\Roster\Domains\Permission\Http\Requests\UpdatePermissionRequest;

final class PermissionController
{
    public function index(IndexPermissionsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function update(UpdatePermissionRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(DestroyPermissionRequest $request): Response
    {
        return $request->persist();
    }
}
