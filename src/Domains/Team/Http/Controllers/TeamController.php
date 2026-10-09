<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use RefactorCircus\Roster\Domains\Team\Http\Requests\DestroyTeamRequest;
use RefactorCircus\Roster\Domains\Team\Http\Requests\IndexTeamsRequest;
use RefactorCircus\Roster\Domains\Team\Http\Requests\ShowTeamRequest;
use RefactorCircus\Roster\Domains\Team\Http\Requests\StoreTeamRequest;
use RefactorCircus\Roster\Domains\Team\Http\Requests\UpdateTeamRequest;

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
