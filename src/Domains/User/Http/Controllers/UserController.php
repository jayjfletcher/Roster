<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use JayI\Roster\Domains\User\Http\Requests\DestroyUserRequest;
use JayI\Roster\Domains\User\Http\Requests\IndexUsersRequest;
use JayI\Roster\Domains\User\Http\Requests\ShowUserRequest;
use JayI\Roster\Domains\User\Http\Requests\StoreUserRequest;
use JayI\Roster\Domains\User\Http\Requests\UpdateUserRequest;

final class UserController
{
    public function index(IndexUsersRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function show(ShowUserRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function update(UpdateUserRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(DestroyUserRequest $request): Response
    {
        return $request->persist();
    }
}
