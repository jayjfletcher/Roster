<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use JayI\Roster\Access\Authorizer;
use JayI\Roster\Audit\Surface;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Team;
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
        return app(Authorizer::class)->check($this->actor(), $this->ability(), $this->scope(), $this->self());
    }

    /**
     * The permission the request's Action needs.
     */
    abstract protected function ability(): string;

    /**
     * The organization or team the permission is checked in; null for global.
     */
    protected function scope(): Organization|Team|null
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
