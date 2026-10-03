<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use JayI\Roster\Domains\Audit\Services\Surface;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Permission\Services\Authorizer;
use JayI\Roster\Domains\Team\Models\TeamModel;
use Laravel\Mcp\Request as McpRequest;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

/**
 * Base MCP request.
 *
 * Mirrors the HTTP FormRequest `persist()` pattern so tools stay one line and
 * both surfaces resolve the same Actions.
 *
 * Every request names the permission its Action needs and the organization
 * or team it applies to; the Authorizer checks it against the authenticated
 * MCP user.
 */
abstract class Request extends McpRequest
{
    final public function persist(): Response|ResponseFactory
    {
        try {
            if (! $this->authorize()) {
                return Response::error('Unauthorized.');
            }

            // Everything this call changes is recorded as coming over MCP.
            return app(Surface::class)->using('mcp', fn (): Response|ResponseFactory => $this->handle($this->validated()));
        } catch (ModelNotFoundException) {
            return Response::error('Not found.');
        }
    }

    /**
     * Handle the validated tool call.
     *
     * @param  array<string, mixed>  $validated
     */
    abstract protected function handle(array $validated): Response|ResponseFactory;

    /**
     * Wrap a resolved collection in a `data` envelope.
     *
     * `Response::structured([])` throws, so an empty list must still ship
     * inside a non-empty `{ "data": [...] }` payload.
     *
     * @param  array<int, mixed>  $items
     * @param  array<string, mixed>  $meta
     */
    protected function structuredCollection(array $items, array $meta = []): ResponseFactory
    {
        return Response::structured(['data' => $items] + $meta);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [];
    }

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
     * yourself.
     */
    protected function actor(): ?Model
    {
        $user = $this->user();

        return $user instanceof Model ? $user : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(): array
    {
        return Validator::validate($this->all(), $this->rules());
    }
}
