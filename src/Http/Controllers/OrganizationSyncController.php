<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Controllers;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Http\Requests\LinkOrganizationRequest;
use JayI\Roster\Http\Requests\SyncOrganizationRequest;
use JayI\Roster\Http\Requests\SyncOrganizationsRequest;
use JayI\Roster\Http\Requests\UnlinkOrganizationRequest;

final class OrganizationSyncController
{
    public function sync(SyncOrganizationRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function syncMany(SyncOrganizationsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function link(LinkOrganizationRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function unlink(UnlinkOrganizationRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
