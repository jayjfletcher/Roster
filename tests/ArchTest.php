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

arch('actions are final classes')
    ->expect('JayI\Roster\Actions')
    ->classes()
    ->toBeFinal();

arch('actions declare their validation rules')
    ->expect('JayI\Roster\Actions')
    ->classes()
    ->toHaveMethod('rules');

arch('package models are final, except the extendable owned-mode user')
    ->expect('JayI\Roster\Models')
    ->classes()
    ->toBeFinal()
    ->ignoring('JayI\Roster\Models\User');

arch('every action is reachable from the HTTP API, MCP and a UI')
    ->expect(fn (): array => parityGaps())
    ->toBeEmpty();

arch('every API and MCP request names the permission it needs')
    ->expect(['JayI\Roster\Http\Requests', 'JayI\Roster\Mcp\Requests'])
    ->classes()
    ->toHaveMethod('ability');
