<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

beforeEach(fn () => signInAsSuperAdmin());

it('uploads a members csv, previews it and confirms', function (): void {
    organization(attributes: ['name' => 'Acme', 'domains' => ['acme.test']]);
    $csv = tempnam(sys_get_temp_dir(), 'roster').'.csv';
    file_put_contents($csv, "email,name\nnew@acme.test,New Person\nfriend@example.com,Friend\nbad,Bad\n");

    visit('/atrium/roster/organizations/acme')
        ->click('@organization-transfers')
        ->select('#import-type', 'import_members')
        ->attach('#atrium-file', $csv)
        ->click('@start-import')
        ->assertSee('Awaiting confirmation')
        ->assertSee('Outside the organization\'s domains; an invitation is sent.')
        ->assertSee('Not a valid email address.')
        ->click('@confirm-import')
        ->assertSee('Import confirmed')
        ->assertSee('Completed');
});
