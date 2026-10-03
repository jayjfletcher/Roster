<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Organization\Enums;

/**
 * How a user joined an organization.
 */
enum MembershipSource: string
{
    case Direct = 'direct';
    case Invitation = 'invitation';
    case Domain = 'domain';
    case Personal = 'personal';
    case Scim = 'scim';
    case Import = 'import';
}
