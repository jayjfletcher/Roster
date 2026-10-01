<?php

declare(strict_types=1);

use JayI\Roster\Actions\CreateUserAction;
use JayI\Roster\Models\AuditEntry;

it('records nothing when the audit log is turned off', function (): void {
    app(CreateUserAction::class)->execute(['name' => 'Ada', 'email' => 'ada@example.com']);

    expect(AuditEntry::query()->count())->toBe(0);
});
