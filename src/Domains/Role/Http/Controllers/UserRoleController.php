<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use JayI\Roster\Domains\Permission\Http\Requests\ShowUserPermissionsRequest;
use JayI\Roster\Domains\Role\Http\Requests\AssignRoleRequest;
use JayI\Roster\Domains\Role\Http\Requests\IndexUserRolesRequest;
use JayI\Roster\Domains\Role\Http\Requests\RevokeRoleRequest;

final class UserRoleController
{
    public function index(IndexUserRolesRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function store(AssignRoleRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(RevokeRoleRequest $request): Response
    {
        return $request->persist();
    }

    public function permissions(ShowUserPermissionsRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
