<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use JayI\Roster\Http\Requests\AssignRoleRequest;
use JayI\Roster\Http\Requests\IndexUserRolesRequest;
use JayI\Roster\Http\Requests\RevokeRoleRequest;
use JayI\Roster\Http\Requests\ShowUserPermissionsRequest;

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
