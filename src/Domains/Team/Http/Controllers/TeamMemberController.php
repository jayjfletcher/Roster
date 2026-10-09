<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use RefactorCircus\Roster\Domains\Team\Http\Requests\DestroyTeamMemberRequest;
use RefactorCircus\Roster\Domains\Team\Http\Requests\StoreTeamMemberRequest;

final class TeamMemberController
{
    public function store(StoreTeamMemberRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(DestroyTeamMemberRequest $request): Response
    {
        return $request->persist();
    }
}
