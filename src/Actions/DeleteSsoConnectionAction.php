<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Support\Facades\DB;
use JayI\Roster\Events\Action\SsoConnectionDeletedActionEvent;
use JayI\Roster\Events\Action\SsoConnectionDeletingActionEvent;
use JayI\Roster\Models\SsoConnection;

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
    public function execute(SsoConnection $connection): void
    {
        SsoConnectionDeletingActionEvent::dispatch($connection);

        DB::transaction(fn () => $connection->delete());

        SsoConnectionDeletedActionEvent::dispatch($connection);
    }
}
