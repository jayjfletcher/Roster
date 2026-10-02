<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Access\Authorizer;
use JayI\Roster\Actions\ShowImportTemplateAction;
use JayI\Roster\Enums\TransferType;
use JayI\Roster\Mcp\Request;
use Laravel\Mcp\Response;

/**
 * Templates hold no data, so any signed-in user may read them.
 */
final class ShowImportTemplateMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return ! app(Authorizer::class)->enabled() || $this->actor() !== null;
    }

    protected function ability(): string
    {
        return '';
    }

    protected function rules(): array
    {
        return ShowImportTemplateAction::rules();
    }

    protected function handle(array $validated): Response
    {
        return Response::text(app(ShowImportTemplateAction::class)->execute(TransferType::from((string) $validated['type'])));
    }
}
