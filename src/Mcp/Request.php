<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Mcp;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Foundation\Mcp\Requests\Request as BaseRequest;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

/**
 * Base MCP request for Roster, on refactor-circus/foundation's shared request.
 *
 * Mirrors the HTTP request's `persist()` pattern so tools stay one line and
 * both surfaces resolve the same Actions; the shared base marks every call
 * as coming over MCP.
 *
 * Roster authorizes through its own permissions rather than the Gate: every
 * request names the permission its Action needs and the organization or team
 * it applies to, and Roster's Authorizer checks it against the authenticated
 * MCP user.
 */
abstract class Request extends BaseRequest
{
    protected function authorize(): bool
    {
        if (app(Authorizer::class)->check($this->actor(), $this->ability(), $this->scope(), $this->self())) {
            return true;
        }

        return ($this->organizations() ?? []) !== [];
    }

    /**
     * Lists return true: asked without an organization of their own, a user
     * holding the permission only in some organizations sees what falls
     * within those, rather than being refused.
     */
    protected function acrossOrganizations(): bool
    {
        return false;
    }

    /**
     * The organizations a list is limited to, or null for no limit: the user
     * holds the permission globally, or the request names its own scope.
     *
     * @return array<int, int|string>|null
     */
    protected function organizations(): ?array
    {
        if (! $this->acrossOrganizations() || $this->scope() !== null) {
            return null;
        }

        $authorizer = app(Authorizer::class);

        if ($authorizer->check($this->actor(), $this->ability(), null, $this->self())) {
            return null;
        }

        return $authorizer->organizationsWith($this->actor(), $this->ability());
    }

    /**
     * The permission the request's Action needs.
     */
    abstract protected function ability(): string;

    /**
     * The organization or team the permission is checked in; null for global.
     */
    protected function scope(): OrganizationModel|TeamModel|null
    {
        return null;
    }

    /**
     * The user the request is about, when everyone may do it to themselves.
     */
    protected function self(): ?Model
    {
        return null;
    }

    /**
     * The authenticated user, passed to Actions that guard against acting on
     * yourself - also with `roster.authorization` off, unlike the shared base.
     */
    protected function actor(): ?Model
    {
        $user = $this->user();

        return $user instanceof Model ? $user : null;
    }
}
