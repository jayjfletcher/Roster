<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Foundation\Mcp\Tool;
use RefactorCircus\Roster\Domains\Sso\Mcp\Requests\UpdateSsoConnectionMcpRequest;

#[Description('Change an SSO connection. The protocol cannot change; omit client_secret to keep it.')]
final class UpdateSsoConnectionTool extends Tool
{
    public function handle(UpdateSsoConnectionMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'connection' => $schema->string()->description('The connection slug.')->required(),
            'name' => $schema->string()->description('Display name.'),
            'slug' => $schema->string()->description('New URL identifier.'),
            'issuer' => $schema->string()->description('OIDC: the issuer URL (https), e.g. https://example.okta.com.'),
            'tenant' => $schema->string()->description('Microsoft Entra ID: the tenant ID or verified domain. common/organizations/consumers are refused.'),
            'client_id' => $schema->string()->description('OIDC / Entra ID: the application (client) id.'),
            'client_secret' => $schema->string()->description('OIDC / Entra ID: the client secret. Never returned; omit on update to keep it.'),
            'metadata_url' => $schema->string()->description('SAML: the identity provider metadata URL (preferred).'),
            'entity_id' => $schema->string()->description('SAML without metadata: the IdP entity ID.'),
            'sso_url' => $schema->string()->description('SAML without metadata: the IdP single sign-on URL.'),
            'certificate' => $schema->string()->description('SAML without metadata: the IdP x509 signing certificate.'),
            'jit' => $schema->boolean()->description('Create accounts on first sign-in for the organization\'s domains. Defaults to true.'),
            'enforced' => $schema->boolean()->description('Require SSO for the organization\'s domains. Defaults to false.'),
            'enabled' => $schema->boolean()->description('Defaults to true.'),
        ];
    }
}
