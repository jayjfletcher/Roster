<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Http\Requests\LinkOrganizationRequest;
use RefactorCircus\Roster\Domains\Organization\Http\Requests\SyncOrganizationRequest;
use RefactorCircus\Roster\Domains\Organization\Http\Requests\SyncOrganizationsRequest;
use RefactorCircus\Roster\Domains\Organization\Http\Requests\UnlinkOrganizationRequest;

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
