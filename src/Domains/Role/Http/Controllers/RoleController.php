<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use JayI\Roster\Domains\Role\Http\Requests\DestroyRoleRequest;
use JayI\Roster\Domains\Role\Http\Requests\IndexRolesRequest;
use JayI\Roster\Domains\Role\Http\Requests\ShowRoleRequest;
use JayI\Roster\Domains\Role\Http\Requests\StoreRoleRequest;
use JayI\Roster\Domains\Role\Http\Requests\UpdateRoleRequest;

final class RoleController
{
    public function index(IndexRolesRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function show(ShowRoleRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function update(UpdateRoleRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(DestroyRoleRequest $request): Response
    {
        return $request->persist();
    }
}
