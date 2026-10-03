<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use JayI\Roster\Domains\Organization\Http\Requests\PurgeOrganizationRequest;
use JayI\Roster\Domains\Organization\Http\Requests\RestoreOrganizationRequest;
use JayI\Roster\Domains\User\Http\Requests\PurgeUserRequest;
use JayI\Roster\Domains\User\Http\Requests\RestoreUserRequest;

/**
 * Restoring deleted users and organizations, and deleting them for good.
 */
final class TrashController
{
    public function restoreUser(RestoreUserRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function purgeUser(PurgeUserRequest $request): Response
    {
        return $request->persist();
    }

    public function restoreOrganization(RestoreOrganizationRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function purgeOrganization(PurgeOrganizationRequest $request): Response
    {
        return $request->persist();
    }
}
