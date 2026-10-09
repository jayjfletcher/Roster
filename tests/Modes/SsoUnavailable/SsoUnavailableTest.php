<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Roster\Domains\Sso\Exceptions\SsoUnavailableException;
use RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Roster\Domains\Sso\Services\Sso;

it('registers no sign-in routes but still manages connections', function (): void {
    expect(Route::has('roster.sso.start'))->toBeFalse()
        ->and(Route::has('roster.sso-connections.show'))->toBeTrue();

    $this->postJson(route('roster.organizations.sso-connections.store', organization(attributes: ['name' => 'Acme'])->slug), [
        'name' => 'Entra', 'protocol' => 'azure', 'tenant' => 'acme.onmicrosoft.com', 'client_id' => 'a', 'client_secret' => 'b',
    ])->assertCreated()->assertJsonPath('data.callback_url', null);
});

it('explains which packages to install', function (): void {
    app(Sso::class)->provider(SsoConnectionModel::factory()->azure()->create(['organization_id' => organization()->id]));
})->throws(SsoUnavailableException::class, 'composer require laravel/socialite socialiteproviders/microsoft-azure');
