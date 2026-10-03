<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use JayI\Roster\Domains\Organization\Http\Requests\DestroyMemberRequest;
use JayI\Roster\Domains\Organization\Http\Requests\IndexMembersRequest;
use JayI\Roster\Domains\Organization\Http\Requests\StoreMemberRequest;

final class MemberController
{
    public function index(IndexMembersRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function store(StoreMemberRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(DestroyMemberRequest $request): Response
    {
        return $request->persist();
    }
}
