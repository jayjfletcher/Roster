<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Impersonation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Impersonation\Http\Requests\IndexImpersonationsRequest;
use JayI\Roster\Domains\Impersonation\Http\Requests\StartImpersonationRequest;
use JayI\Roster\Domains\Impersonation\Http\Requests\StopImpersonationRequest;

final class ImpersonationController
{
    public function index(IndexImpersonationsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function store(StartImpersonationRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(StopImpersonationRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
