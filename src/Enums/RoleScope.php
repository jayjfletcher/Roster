<?php

declare(strict_types=1);

namespace JayI\Roster\Enums;

/**
 * Where a role applies: everywhere, within one organization, or within one
 * team.
 */
enum RoleScope: string
{
    case Global = 'global';
    case Organization = 'organization';
    case Team = 'team';

    public function label(): string
    {
        return __('roster::roster.scope_'.$this->value);
    }
}
