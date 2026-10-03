<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Sso\Actions;

use JayI\Roster\Domains\Sso\Events\SsoConnectionShowingActionEvent;
use JayI\Roster\Domains\Sso\Events\SsoConnectionShownActionEvent;
use JayI\Roster\Domains\Sso\Models\SsoConnectionModel;

final class ShowSsoConnectionAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(SsoConnectionModel $connection): SsoConnectionModel
    {
        SsoConnectionShowingActionEvent::dispatch($connection);

        $connection->loadMissing('organization')->loadCount('identities');

        SsoConnectionShownActionEvent::dispatch($connection);

        return $connection;
    }
}
