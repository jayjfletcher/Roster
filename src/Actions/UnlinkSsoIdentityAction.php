<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Support\Facades\DB;
use JayI\Roster\Events\Action\SsoIdentityUnlinkedActionEvent;
use JayI\Roster\Events\Action\SsoIdentityUnlinkingActionEvent;
use JayI\Roster\Models\SsoIdentity;

final class UnlinkSsoIdentityAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    /**
     * Unlink an identity; signing in through that provider would then go
     * through the linking rules again.
     */
    public function execute(SsoIdentity $identity): void
    {
        SsoIdentityUnlinkingActionEvent::dispatch($identity);

        DB::transaction(fn () => $identity->delete());

        SsoIdentityUnlinkedActionEvent::dispatch($identity);
    }
}
