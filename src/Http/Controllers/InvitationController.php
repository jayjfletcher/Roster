<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Controllers;

use Illuminate\Http\JsonResponse;
use JayI\Roster\Http\Requests\AcceptInvitationRequest;
use JayI\Roster\Http\Requests\DeclineInvitationRequest;
use JayI\Roster\Http\Requests\IndexInvitationsRequest;
use JayI\Roster\Http\Requests\RevokeInvitationRequest;
use JayI\Roster\Http\Requests\StoreInvitationRequest;

final class InvitationController
{
    public function index(IndexInvitationsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function store(StoreInvitationRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function revoke(RevokeInvitationRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function accept(AcceptInvitationRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function decline(DeclineInvitationRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
