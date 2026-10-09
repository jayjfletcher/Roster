<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\User\Http\Requests\ApproveUserRequest;
use RefactorCircus\Roster\Domains\User\Http\Requests\DeactivateUserRequest;
use RefactorCircus\Roster\Domains\User\Http\Requests\ReactivateUserRequest;
use RefactorCircus\Roster\Domains\User\Http\Requests\RejectUserRequest;
use RefactorCircus\Roster\Domains\User\Http\Requests\SuspendUserRequest;

final class UserStatusController
{
    public function suspend(SuspendUserRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function deactivate(DeactivateUserRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function approve(ApproveUserRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function reject(RejectUserRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function reactivate(ReactivateUserRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
