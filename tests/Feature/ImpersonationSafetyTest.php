<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Domains\Impersonation\Actions\StartImpersonationAction;
use JayI\Roster\Domains\Permission\Services\Authorizer;
use JayI\Roster\Domains\User\Actions\UpdateUserAction;

beforeEach(function (): void {
    config()->set('roster.authorization', true);
    config()->set('roster.impersonation.blocked', ['roster.roles.*', 'billing.*', 'roster.users.delete']);
    Gate::define('billing.refund', fn (): bool => true);

    $this->admin = user(['name' => 'Admin']);
    grant($this->admin, 'super-admin');
    $this->ada = user(['name' => 'Ada', 'email' => 'ada@example.com']);
    grant($this->ada, 'super-admin');
    $this->actingAs($this->admin);

    $started = app(StartImpersonationAction::class)->execute($this->ada, ['reason' => 'Ticket 42'], $this->admin);
    $this->get($started->url);
    $this->impersonation = $started->impersonation->refresh();
});

it('blocks listed permissions and app abilities, even for super-admins', function (): void {
    expect(Auth::id())->toBe($this->ada->getKey())
        ->and(app(Authorizer::class)->check($this->ada, 'roster.roles.assign'))->toBeFalse()
        ->and(app(Authorizer::class)->check($this->ada, 'roster.users.view'))->toBeTrue()
        ->and(Gate::allows('billing.refund'))->toBeFalse()
        ->and(Gate::allows('roster.users.delete'))->toBeFalse()
        ->and(Gate::allows('roster.users.view'))->toBeTrue();
});

it('locks the impersonated account email and password', function (): void {
    expect(fn () => app(UpdateUserAction::class)->execute($this->ada, ['email' => 'mine@evil.test']))
        ->toThrow(ValidationException::class)
        ->and(fn () => app(UpdateUserAction::class)->execute($this->ada, ['password' => 'new-password']))
        ->toThrow(ValidationException::class);

    // Other fields, and other accounts, are fine.
    expect(app(UpdateUserAction::class)->execute($this->ada, ['name' => 'Ada L'])->getAttribute('name'))->toBe('Ada L');
});
