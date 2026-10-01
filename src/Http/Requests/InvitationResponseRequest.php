<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Http\Request;

/**
 * Accepting or declining acts as the signed-in user, so a guest gets a 401
 * rather than a validation error.
 */
abstract class InvitationResponseRequest extends Request
{
    /**
     * Answering needs no permission: only the invitee, signed in, can do it.
     */
    protected function ability(): string
    {
        return '';
    }

    public function authorize(): bool
    {
        return $this->actor() !== null;
    }

    protected function failedAuthorization(): void
    {
        throw new AuthenticationException;
    }

    protected function respondent(): Model
    {
        return $this->actor() ?? throw new AuthenticationException;
    }

    /**
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        return ['token' => $this->route('token')] + $this->all();
    }
}
