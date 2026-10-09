<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Invitation\Resources\InvitationResource;
use RefactorCircus\Roster\Mcp\Request;

/**
 * Accepting or declining acts as the authenticated MCP user.
 */
abstract class InvitationResponseMcpRequest extends Request
{
    /**
     * Answering needs no permission: only the invitee, signed in, can do it.
     */
    protected function ability(): string
    {
        return '';
    }

    protected function authorize(): bool
    {
        return $this->actor() !== null;
    }

    protected function respondent(): Model
    {
        /** @var Model */
        return $this->actor();
    }

    protected function respondWithInvitation(InvitationModel $invitation): ResponseFactory
    {
        return Response::structured(['data' => (new InvitationResource($invitation))->resolve()]);
    }
}
