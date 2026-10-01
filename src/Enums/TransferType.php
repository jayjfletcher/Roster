<?php

declare(strict_types=1);

namespace JayI\Roster\Enums;

enum TransferType: string
{
    case ImportMembers = 'import_members';
    case ImportUsers = 'import_users';
    case ImportTeams = 'import_teams';
    case ExportMembers = 'export_members';
    case ExportUsers = 'export_users';
    case ExportAudit = 'export_audit';

    public function isImport(): bool
    {
        return str_starts_with($this->value, 'import_');
    }

    /**
     * Whether it works within one organization (and so needs one).
     */
    public function needsOrganization(): bool
    {
        return in_array($this, [self::ImportMembers, self::ImportTeams, self::ExportMembers], true);
    }

    /**
     * The permission it needs, checked in the organization when it has one.
     */
    public function permission(): string
    {
        return match ($this) {
            self::ImportMembers => 'roster.members.manage',
            self::ImportTeams => 'roster.teams.manage',
            self::ImportUsers => 'roster.users.create',
            self::ExportMembers => 'roster.members.view',
            self::ExportUsers => 'roster.users.view',
            self::ExportAudit => 'roster.audit.view',
        };
    }

    /**
     * The columns an import's header must have, and may have.
     *
     * @return array{required: array<int, string>, optional: array<int, string>}
     */
    public function columns(): array
    {
        return match ($this) {
            self::ImportMembers => ['required' => ['email'], 'optional' => ['name', 'display_name', 'teams', 'role']],
            self::ImportUsers => ['required' => ['email'], 'optional' => ['name', 'display_name']],
            self::ImportTeams => ['required' => ['name'], 'optional' => ['slug', 'members']],
            default => ['required' => [], 'optional' => []],
        };
    }

    public function label(): string
    {
        return __('roster::roster.transfer_'.$this->value);
    }
}
