<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use JayI\Roster\Events\Action\SsoConnectionShowingActionEvent;
use JayI\Roster\Events\Action\SsoConnectionShownActionEvent;
use JayI\Roster\Models\SsoConnection;

final class ShowSsoConnectionAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(SsoConnection $connection): SsoConnection
    {
        SsoConnectionShowingActionEvent::dispatch($connection);

        $connection->loadMissing('organization')->loadCount('identities');

        SsoConnectionShownActionEvent::dispatch($connection);

        return $connection;
    }
}
