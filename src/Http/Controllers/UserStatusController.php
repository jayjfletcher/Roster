<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Controllers;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Http\Requests\DeactivateUserRequest;
use JayI\Roster\Http\Requests\ReactivateUserRequest;
use JayI\Roster\Http\Requests\SuspendUserRequest;

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

    public function reactivate(ReactivateUserRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
