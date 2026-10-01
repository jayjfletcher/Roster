<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Actions\RevokeScimTokenAction;
use JayI\Roster\Http\Resources\ScimTokenResource;
use JayI\Roster\Mcp\Request;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\ScimToken;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class RevokeScimTokenMcpRequest extends Request
{
    private ?ScimToken $resolved = null;

    protected function ability(): string
    {
        return 'roster.scim.manage';
    }

    protected function scope(): ?Organization
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

    private function token(): ScimToken
    {
        return $this->resolved ??= ScimToken::query()->whereKey($this->get('token'))->firstOrFail();
    }
}
