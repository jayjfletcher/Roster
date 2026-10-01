<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use JayI\Roster\Events\Action\TransferShowingActionEvent;
use JayI\Roster\Events\Action\TransferShownActionEvent;
use JayI\Roster\Models\Transfer;

final class ShowTransferAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(Transfer $transfer): Transfer
    {
        TransferShowingActionEvent::dispatch($transfer);

        $transfer->loadMissing(['organization', 'requester']);

        TransferShownActionEvent::dispatch($transfer);

        return $transfer;
    }
}
