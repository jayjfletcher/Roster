<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use JayI\Roster\Http\Requests\DestroyOrganizationRequest;
use JayI\Roster\Http\Requests\IndexOrganizationsRequest;
use JayI\Roster\Http\Requests\ShowOrganizationRequest;
use JayI\Roster\Http\Requests\StoreOrganizationRequest;
use JayI\Roster\Http\Requests\TransferOwnershipRequest;
use JayI\Roster\Http\Requests\UpdateOrganizationRequest;

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
