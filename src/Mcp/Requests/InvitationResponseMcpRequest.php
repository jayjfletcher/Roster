<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Http\Resources\InvitationResource;
use JayI\Roster\Mcp\Request;
use JayI\Roster\Models\Invitation;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

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

    protected function respondWithInvitation(Invitation $invitation): ResponseFactory
    {
        return Response::structured(['data' => (new InvitationResource($invitation))->resolve()]);
    }
}
