<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Controllers;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Http\Requests\JoinByDomainRequest;
use JayI\Roster\Http\Requests\SwitchContextRequest;

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
