<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Controllers;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Http\Requests\UpdateProfileRequest;

final class UserProfileController
{
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
