<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Controllers;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Http\Requests\IndexImpersonationsRequest;
use JayI\Roster\Http\Requests\StartImpersonationRequest;
use JayI\Roster\Http\Requests\StopImpersonationRequest;

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
