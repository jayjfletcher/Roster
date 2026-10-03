<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Transfer\Actions;

use Illuminate\Validation\Rule;
use JayI\Roster\Domains\Transfer\Enums\TransferType;
use JayI\Roster\Domains\Transfer\Events\ImportTemplateShowingActionEvent;
use JayI\Roster\Domains\Transfer\Events\ImportTemplateShownActionEvent;
use JayI\Roster\Domains\Transfer\Services\Transfers;

final class ShowImportTemplateAction
{
    public function __construct(private readonly Transfers $transfers) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_map(
                fn (TransferType $type): string => $type->value,
                array_filter(TransferType::cases(), fn (TransferType $type): bool => $type->isImport()),
            ))],
        ];
    }

    /**
     * The CSV template for an import type, as text. Works without jayi/impex,
     * so files can be prepared before imports are set up.
     */
    public function execute(TransferType $type): string
    {
        ImportTemplateShowingActionEvent::dispatch($type->value);

        $template = $this->transfers->template($type);

        ImportTemplateShownActionEvent::dispatch($type->value);

        return $template;
    }
}
