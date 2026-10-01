<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use JayI\Roster\Http\Requests\DestroyTeamRequest;
use JayI\Roster\Http\Requests\IndexTeamsRequest;
use JayI\Roster\Http\Requests\ShowTeamRequest;
use JayI\Roster\Http\Requests\StoreTeamRequest;
use JayI\Roster\Http\Requests\UpdateTeamRequest;

final class TeamController
{
    public function index(IndexTeamsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function store(StoreTeamRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function show(ShowTeamRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function update(UpdateTeamRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(DestroyTeamRequest $request): Response
    {
        return $request->persist();
    }
}
