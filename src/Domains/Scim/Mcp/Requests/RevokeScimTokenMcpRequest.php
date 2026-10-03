<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Scim\Mcp\Requests;

use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Scim\Actions\RevokeScimTokenAction;
use JayI\Roster\Domains\Scim\Models\ScimTokenModel;
use JayI\Roster\Domains\Scim\Resources\ScimTokenResource;
use JayI\Roster\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class RevokeScimTokenMcpRequest extends Request
{
    private ?ScimTokenModel $resolved = null;

    protected function ability(): string
    {
        return 'roster.scim.manage';
    }

    protected function scope(): ?OrganizationModel
    {
        return $this->token()->organization;
    }

    protected function rules(): array
    {
        return RevokeScimTokenAction::rules() + ['token' => ['required', 'string']];
    }

    protected function handle(array $validated): ResponseFactory
    {
        return Response::structured(['data' => (new ScimTokenResource(app(RevokeScimTokenAction::class)->execute($this->token())))->resolve()]);
    }

    private function token(): ScimTokenModel
    {
        return $this->resolved ??= ScimTokenModel::query()->whereKey($this->get('token'))->firstOrFail();
    }
}
