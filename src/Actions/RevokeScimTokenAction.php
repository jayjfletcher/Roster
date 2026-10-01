<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Support\Facades\DB;
use JayI\Roster\Events\Action\ScimTokenRevokedActionEvent;
use JayI\Roster\Events\Action\ScimTokenRevokingActionEvent;
use JayI\Roster\Models\ScimToken;

final class RevokeScimTokenAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    /**
     * Stop a token working. Revoking twice changes nothing.
     */
    public function execute(ScimToken $token): ScimToken
    {
        if ($token->revoked_at !== null) {
            return $token;
        }

        ScimTokenRevokingActionEvent::dispatch($token);

        DB::transaction(fn () => $token->update(['revoked_at' => now()]));

        ScimTokenRevokedActionEvent::dispatch($token);

        return $token->load(['organization', 'ssoConnection']);
    }
}
