<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use JayI\Roster\Enums\TransferStatus;
use JayI\Roster\Models\Transfer;
use Workbench\App\Models\User;

beforeEach(function (): void {
    $this->actingAs(user());
    organization(attributes: ['name' => 'Acme', 'domains' => ['acme.test']]);
});

it('uploads, previews and confirms an import', function (): void {
    $this->get(route('atrium.roster.transfers.index', ['organization' => 'acme']))->assertOk()->assertSee('Upload and preview');

    $file = UploadedFile::fake()->createWithContent('m.csv', "email\nnew@acme.test\n");
    $response = $this->post(route('atrium.roster.transfers.import'), ['type' => 'import_members', 'organization' => 'acme', 'file' => $file]);

    $transfer = Transfer::query()->sole();
    $response->assertRedirect(route('atrium.roster.transfers.show', $transfer->id));

    $this->get(route('atrium.roster.transfers.show', $transfer->id))->assertOk()->assertSee('Awaiting confirmation')->assertSee('Confirm import');

    $this->post(route('atrium.roster.transfers.confirm', $transfer->id))->assertRedirect();

    expect($transfer->refresh()->status)->toBe(TransferStatus::Completed)
        ->and(User::query()->where('email', 'new@acme.test')->exists())->toBeTrue();
});

it('exports and downloads, and links from organizations and users', function (): void {
    $this->post(route('atrium.roster.transfers.export'), ['type' => 'export_members', 'organization' => 'acme'])->assertRedirect();
    $transfer = Transfer::query()->sole();

    $this->get(route('atrium.roster.transfers.show', $transfer->id))->assertOk()->assertSee('Download CSV');
    $this->get(route('atrium.roster.transfers.download', $transfer->id))->assertOk();

    $this->get(route('atrium.roster.organizations.show', 'acme'))->assertSee(route('atrium.roster.transfers.index', ['organization' => 'acme']));
    $this->get(route('atrium.roster.users.index'))->assertSee(route('atrium.roster.transfers.index'));
});

it('cancels from the page', function (): void {
    $this->post(route('atrium.roster.transfers.import'), ['type' => 'import_users', 'file' => UploadedFile::fake()->createWithContent('u.csv', "email\na@b.test")]);
    $transfer = Transfer::query()->sole();

    $this->delete(route('atrium.roster.transfers.cancel', $transfer->id))->assertRedirect();

    expect($transfer->refresh()->status)->toBe(TransferStatus::Cancelled);
});
