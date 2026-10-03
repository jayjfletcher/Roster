<?php

declare(strict_types=1);

use JayI\Roster\Domains\Audit\Models\AuditEntryModel;
use JayI\Roster\Domains\User\Actions\CreateUserAction;

it('records nothing when the audit log is turned off', function (): void {
    app(CreateUserAction::class)->execute(['name' => 'Ada', 'email' => 'ada@example.com']);

    expect(AuditEntryModel::query()->count())->toBe(0);
});
