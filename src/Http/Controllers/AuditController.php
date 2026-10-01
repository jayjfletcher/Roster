<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Controllers;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Http\Requests\IndexAuditRequest;
use JayI\Roster\Http\Requests\ShowAuditRequest;
use JayI\Roster\Http\Requests\StoreAuditRequest;

final class AuditController
{
    public function index(IndexAuditRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function store(StoreAuditRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function show(ShowAuditRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
