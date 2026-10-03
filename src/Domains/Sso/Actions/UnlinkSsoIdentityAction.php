<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Sso\Actions;

use Illuminate\Support\Facades\DB;
use JayI\Roster\Domains\Sso\Events\SsoIdentityUnlinkedActionEvent;
use JayI\Roster\Domains\Sso\Events\SsoIdentityUnlinkingActionEvent;
use JayI\Roster\Domains\Sso\Models\SsoIdentityModel;

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
    public function execute(SsoIdentityModel $identity): void
    {
        SsoIdentityUnlinkingActionEvent::dispatch($identity);

        DB::transaction(fn () => $identity->delete());

        SsoIdentityUnlinkedActionEvent::dispatch($identity);
    }
}
