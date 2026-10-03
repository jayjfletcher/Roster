<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Transfer\Mcp\Requests;

use JayI\Roster\Domains\Permission\Services\Authorizer;
use JayI\Roster\Domains\Transfer\Actions\ShowImportTemplateAction;
use JayI\Roster\Domains\Transfer\Enums\TransferType;
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
