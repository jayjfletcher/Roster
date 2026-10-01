<?php

declare(strict_types=1);

namespace JayI\Roster\Http;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use JayI\Roster\Access\Authorizer;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Team;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base HTTP request.
 *
 * Validation rules come from the Action the request wraps, and `persist()`
 * calls that same Action. The MCP surface does the same, so both speak to one
 * implementation rather than two that drift.
 *
 * Every request names the permission its Action needs and the organization
 * or team it applies to; the Authorizer checks it against the signed-in user.
 */
abstract class Request extends FormRequest
{
    public function authorize(): bool
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
     * Guests get a 401, signed-in users without the permission a 403.
     */
    protected function failedAuthorization(): void
    {
        if ($this->actor() === null) {
            throw new AuthenticationException;
        }

        parent::failedAuthorization();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Execute the request's use case and build the response.
     */
    abstract public function persist(): Response;

    /**
     * The authenticated user, passed to Actions that guard against acting on
     * yourself.
     */
    protected function actor(): ?Model
    {
        $user = $this->user();

        return $user instanceof Model ? $user : null;
    }
}
