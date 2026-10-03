<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Sso\Actions;

use Illuminate\Support\Facades\DB;
use JayI\Roster\Domains\Sso\Events\SsoConnectionDeletedActionEvent;
use JayI\Roster\Domains\Sso\Events\SsoConnectionDeletingActionEvent;
use JayI\Roster\Domains\Sso\Models\SsoConnectionModel;

final class DeleteSsoConnectionAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    /**
     * Remove a connection and its linked identities. Accounts stay.
     */
    public function execute(SsoConnectionModel $connection): void
    {
        SsoConnectionDeletingActionEvent::dispatch($connection);

        DB::transaction(fn () => $connection->delete());

        SsoConnectionDeletedActionEvent::dispatch($connection);
    }
}
