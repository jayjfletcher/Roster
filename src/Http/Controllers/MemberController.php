<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use JayI\Roster\Http\Requests\DestroyMemberRequest;
use JayI\Roster\Http\Requests\IndexMembersRequest;
use JayI\Roster\Http\Requests\StoreMemberRequest;

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
