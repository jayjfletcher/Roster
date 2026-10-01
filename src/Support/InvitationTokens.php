<?php

declare(strict_types=1);

namespace JayI\Roster\Support;

use Illuminate\Support\Str;
use JayI\Roster\Exceptions\InvalidInvitationException;
use JayI\Roster\Models\Invitation;

/**
 * Invitation tokens: only their hash is stored, so a database leak cannot
 * be turned into working invitation links.
 */
final class InvitationTokens
{
    public static function generate(): string
    {
        return Str::random(48);
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function find(string $token): Invitation
    {
        return Invitation::query()->where('token_hash', self::hash($token))->first()
            ?? throw InvalidInvitationException::forToken();
    }
}
