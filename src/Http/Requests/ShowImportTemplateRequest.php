<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use JayI\Roster\Access\Authorizer;
use JayI\Roster\Actions\ShowImportTemplateAction;
use JayI\Roster\Enums\TransferType;
use JayI\Roster\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Templates hold no data - just columns and commented examples - so any
 * signed-in user may download them.
 */
final class ShowImportTemplateRequest extends Request
{
    public function authorize(): bool
    {
        return ! app(Authorizer::class)->enabled() || $this->actor() !== null;
    }

    protected function ability(): string
    {
        return '';
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['type' => $this->route('type')]);
    }

    public function rules(): array
    {
        return ShowImportTemplateAction::rules();
    }

    public function persist(): Response
    {
        $type = TransferType::from((string) $this->validated('type'));

        return response(app(ShowImportTemplateAction::class)->execute($type), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="roster-'.$type->value.'-template.csv"',
        ]);
    }
}
