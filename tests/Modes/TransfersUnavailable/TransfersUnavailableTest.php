<?php

declare(strict_types=1);

use JayI\Roster\Actions\StartImportAction;
use JayI\Roster\Transfers\TransfersUnavailableException;

it('explains which package to install', function (): void {
    app(StartImportAction::class)->execute(['type' => 'import_users', 'content' => "email\na@b.test"], user());
})->throws(TransfersUnavailableException::class, 'composer require jayi/impex');

it('shows the setup note in atrium instead of the forms', function (): void {
    $this->actingAs(user())
        ->get(route('atrium.roster.transfers.index'))
        ->assertOk()
        ->assertSee('composer require jayi/impex')
        ->assertDontSee('Upload and preview');
});
