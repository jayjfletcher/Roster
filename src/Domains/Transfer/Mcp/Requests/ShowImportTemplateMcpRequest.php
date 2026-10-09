<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Mcp\Requests;

use Laravel\Mcp\Response;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;
use RefactorCircus\Roster\Domains\Transfer\Actions\ShowImportTemplateAction;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferType;
use RefactorCircus\Roster\Mcp\Request;

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
