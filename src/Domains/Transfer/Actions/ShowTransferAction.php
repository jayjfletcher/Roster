<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Transfer\Actions;

use JayI\Roster\Domains\Transfer\Events\TransferShowingActionEvent;
use JayI\Roster\Domains\Transfer\Events\TransferShownActionEvent;
use JayI\Roster\Domains\Transfer\Models\TransferModel;

final class ShowTransferAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'rows_page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function execute(TransferModel $transfer): TransferModel
    {
        TransferShowingActionEvent::dispatch($transfer);

        $transfer->loadMissing(['organization', 'requester']);

        TransferShownActionEvent::dispatch($transfer);

        return $transfer;
    }
}
