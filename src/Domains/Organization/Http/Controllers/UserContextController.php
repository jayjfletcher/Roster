<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Http\Requests\JoinByDomainRequest;
use RefactorCircus\Roster\Domains\Organization\Http\Requests\SwitchContextRequest;

final class UserContextController
{
    public function update(SwitchContextRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function domainJoin(JoinByDomainRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
