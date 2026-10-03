<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use JayI\Roster\Domains\Organization\Http\Requests\DestroyOrganizationRequest;
use JayI\Roster\Domains\Organization\Http\Requests\IndexOrganizationsRequest;
use JayI\Roster\Domains\Organization\Http\Requests\ShowOrganizationRequest;
use JayI\Roster\Domains\Organization\Http\Requests\StoreOrganizationRequest;
use JayI\Roster\Domains\Organization\Http\Requests\TransferOwnershipRequest;
use JayI\Roster\Domains\Organization\Http\Requests\UpdateOrganizationRequest;

final class OrganizationController
{
    public function index(IndexOrganizationsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function show(ShowOrganizationRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function update(UpdateOrganizationRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(DestroyOrganizationRequest $request): Response
    {
        return $request->persist();
    }

    public function transfer(TransferOwnershipRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
