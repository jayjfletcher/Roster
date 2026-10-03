<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Http\Controllers;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\User\Http\Requests\UpdateProfileRequest;

final class UserProfileController
{
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
