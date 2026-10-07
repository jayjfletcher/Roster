<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use JayI\Roster\Domains\Transfer\Models\TransferModel;
use Workbench\App\Models\User;

beforeEach(function (): void {
    $this->actingAs($this->owner = user(['email' => 'owner@acme.test']));
    organization($this->owner, ['name' => 'Acme', 'domains' => ['acme.test']]);
});

it('uploads, previews, confirms and lists an import', function (): void {
    $file = UploadedFile::fake()->createWithContent('members.csv', "email,name\nnew@acme.test,New\nbad,Bad\n");

    $id = $this->post(route('roster.imports.store'), ['type' => 'import_members', 'organization' => 'acme', 'file' => $file], ['Accept' => 'application/json'])
        ->assertStatus(202)
        ->assertJsonPath('data.status', 'awaiting_confirmation')
        ->assertJsonPath('data.summary', ['create' => 1, 'error' => 1])
        ->json('data.id');

    $this->getJson(route('roster.transfers.show', $id))
        ->assertOk()
        ->assertJsonPath('data.rows.0.action', 'create')
        ->assertJsonPath('data.rows.1.action', 'error');

    $this->postJson(route('roster.imports.confirm', $id))
        ->assertOk()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.results', ['create' => 1, 'error' => 1]);

    expect(User::query()->where('email', 'new@acme.test')->exists())->toBeTrue();

    $this->getJson(route('roster.transfers.index'))->assertOk()->assertJsonCount(1, 'data')->assertJsonMissingPath('data.0.rows');
});

it('refuses to confirm twice or confirm an export', function (): void {
    $import = $this->postJson(route('roster.imports.store'), ['type' => 'import_users', 'content' => "email\nada@example.com"])->json('data.id');
    $this->postJson(route('roster.imports.confirm', $import))->assertOk();
    $this->postJson(route('roster.imports.confirm', $import))->assertUnprocessable()->assertJsonValidationErrors('transfer');

    $export = $this->postJson(route('roster.exports.store'), ['type' => 'export_users'])->json('data.id');
    $this->postJson(route('roster.imports.confirm', $export))->assertNotFound();
});

it('exports and downloads a csv', function (): void {
    $id = $this->postJson(route('roster.exports.store'), ['type' => 'export_members', 'organization' => 'acme'])
        ->assertStatus(202)
        ->assertJsonPath('data.status', 'completed')
        ->json('data.id');

    $csv = $this->get(route('roster.transfers.download', $id))->assertOk()->streamedContent();

    expect($csv)->toContain('owner@acme.test');
});

it('cancels a transfer awaiting confirmation', function (): void {
    $id = $this->postJson(route('roster.imports.store'), ['type' => 'import_users', 'content' => "email\nada@example.com"])->json('data.id');

    $this->deleteJson(route('roster.transfers.destroy', $id))->assertOk()->assertJsonPath('data.status', 'cancelled');
    $this->deleteJson(route('roster.transfers.destroy', $id))->assertUnprocessable();
});

it('validates the request', function (): void {
    $this->postJson(route('roster.imports.store'), ['type' => 'export_users', 'content' => 'x'])->assertJsonValidationErrors('type');
    $this->postJson(route('roster.imports.store'), ['type' => 'import_users'])->assertJsonValidationErrors('content');
    $this->postJson(route('roster.imports.store'), ['type' => 'import_members', 'content' => "email\na@b.test"])->assertJsonValidationErrors('organization');
    $this->postJson(route('roster.exports.store'), ['type' => 'export_audit'])->assertJsonValidationErrors('type');

    expect(TransferModel::query()->count())->toBe(0);
});
