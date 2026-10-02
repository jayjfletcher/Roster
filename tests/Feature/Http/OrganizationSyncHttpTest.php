<?php

declare(strict_types=1);

it('upserts an organization by its external id', function (): void {
    $this->putJson(route('roster.organizations.sync', ['erp', 'C/100']), ['name' => 'Initech', 'account_number' => 'A-42', 'domains' => ['initech.test']])
        ->assertCreated()
        ->assertJsonPath('outcome', 'created')
        ->assertJsonPath('data.owner', null)
        ->assertJsonPath('data.links.0.source', 'erp')
        ->assertJsonPath('data.links.0.external_id', 'C/100')
        ->assertJsonPath('data.links.0.account_number', 'A-42');

    $this->putJson(route('roster.organizations.sync', ['erp', 'C/100']), ['name' => 'Initech Corp'])
        ->assertOk()
        ->assertJsonPath('outcome', 'updated')
        ->assertJsonPath('data.slug', 'initech');

    $this->getJson(route('roster.organizations.index', ['source' => 'erp', 'account_number' => 'A-42']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Initech Corp');
});

it('validates the record', function (): void {
    $this->putJson(route('roster.organizations.sync', ['ERP!', 'C-1']), ['name' => 'X'])->assertJsonValidationErrors('source');
    $this->putJson(route('roster.organizations.sync', ['erp', 'C-1']), [])->assertJsonValidationErrors('name');
});

it('syncs a batch with per-record results', function (): void {
    $this->postJson(route('roster.organizations.sync-many'), ['records' => [
        ['source' => 'erp', 'external_id' => 'C-1', 'name' => 'One'],
        ['source' => 'erp', 'external_id' => 'C-2'],
    ]])
        ->assertOk()
        ->assertJsonPath('data.0', ['source' => 'erp', 'external_id' => 'C-1', 'outcome' => 'created', 'organization' => 'one', 'errors' => null])
        ->assertJsonPath('data.1.outcome', 'error')
        ->assertJsonPath('data.1.errors.name.0', 'The name field is required.');

    config()->set('roster.organizations.sync_batch', 1);

    $this->postJson(route('roster.organizations.sync-many'), ['records' => [['source' => 'a'], ['source' => 'b']]])->assertJsonValidationErrors('records');
});

it('links and unlinks an organization', function (): void {
    organization(attributes: ['name' => 'Acme']);

    $this->putJson(route('roster.organizations.links.update', ['acme', 'crm']), ['external_id' => '0015g', 'account_number' => 'A-1'])
        ->assertOk()
        ->assertJsonPath('data.links.0.external_id', '0015g');

    $this->deleteJson(route('roster.organizations.links.destroy', ['acme', 'crm']))->assertOk()->assertJsonPath('data.links', []);
    $this->deleteJson(route('roster.organizations.links.destroy', ['acme', 'crm']))->assertJsonValidationErrors('source');
});

it('creates an organization without an owner', function (): void {
    $this->postJson(route('roster.organizations.store'), ['name' => 'Ownerless'])->assertCreated()->assertJsonPath('data.owner', null);
});
