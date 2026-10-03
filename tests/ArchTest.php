<?php

declare(strict_types=1);

arch()->preset()->php();

arch()->preset()->security();

arch('it will not use dd(), ddd(), env(), or exit()')
    ->expect(['dd', 'ddd', 'env', 'exit'])
    ->each->not->toBeUsed();

arch('the package source declares strict types')
    ->expect('JayI\Roster')
    ->toUseStrictTypes();

/**
 * The given sub-namespace of every domain that has one, e.g. `Actions`.
 *
 * @return array<int, string>
 */
function domainNamespaces(string $sub): array
{
    $root = dirname(__DIR__).'/src/Domains';

    return array_map(
        fn (string $path): string => 'JayI\\Roster\\Domains\\'.str_replace('/', '\\', substr($path, strlen($root) + 1)),
        (array) glob($root.'/*/'.$sub, GLOB_ONLYDIR),
    );
}

arch('actions are final classes')
    ->expect(domainNamespaces('Actions'))
    ->classes()
    ->toBeFinal();

arch('actions declare their validation rules')
    ->expect(domainNamespaces('Actions'))
    ->classes()
    ->toHaveMethod('rules');

arch('package models are final, except the extendable owned-mode user')
    ->expect(domainNamespaces('Models'))
    ->classes()
    ->toBeFinal()
    ->ignoring('JayI\Roster\Domains\User\Models\UserModel');

arch('every action is reachable from the HTTP API, MCP and a UI')
    ->expect(fn (): array => parityGaps())
    ->toBeEmpty();

arch('every API and MCP request names the permission it needs')
    ->expect([...domainNamespaces('Http/Requests'), ...domainNamespaces('Mcp/Requests')])
    ->classes()
    ->toHaveMethod('ability');
