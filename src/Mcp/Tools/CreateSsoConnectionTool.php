<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Roster\Mcp\Requests\CreateSsoConnectionMcpRequest;
use JayI\Roster\Mcp\Tool;
use JayI\Roster\Mcp\Tools\Concerns\DescribesOrganization;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Connect an organization to its identity provider. protocol is oidc (issuer, client_id, client_secret), azure for Microsoft Entra ID (tenant, client_id, client_secret) or saml (metadata_url, or entity_id + sso_url + certificate). Returns the callback URL to register at the provider.')]
final class CreateSsoConnectionTool extends Tool
{
    use DescribesOrganization;

    public function handle(CreateSsoConnectionMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->organizationSchema($schema) + [
            'name' => $schema->string()->description('Display name, e.g. Okta.')->required(),
            'slug' => $schema->string()->description('URL identifier. Omit to generate one.'),
            'protocol' => $schema->string()->description('oidc, azure or saml.')->required(),
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
