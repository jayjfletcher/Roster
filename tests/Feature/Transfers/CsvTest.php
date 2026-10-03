<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Domains\Transfer\Support\Csv\Reader;
use JayI\Roster\Domains\Transfer\Support\Csv\Writer;

function reader(string $csv, array $columns = ['required' => ['email'], 'optional' => ['name']]): Reader
{
    Storage::disk('local')->put('csv-test.csv', $csv);

    return new Reader(Storage::disk('local'), 'csv-test.csv', $columns);
}

it('maps rows by a case-insensitive header and tolerates a BOM', function (): void {
    $reader = reader("\u{FEFF}Email , NAME\n ada@example.com ,Ada\n\n");

    expect($reader->validate())->toBe(1)
        ->and(iterator_to_array($reader->rows(), false)[0]['values'])->toBe(['email' => 'ada@example.com', 'name' => 'Ada']);
});

it('rejects unsafe or malformed files', function (string $csv, string $message): void {
    expect(fn () => reader($csv)->validate())->toThrow(ValidationException::class, $message);
})->with([
    'password column' => ["email,password\na@b.test,x", 'Passwords cannot be imported'],
    'missing column' => ["name\nAda", 'Missing required columns: email'],
    'unknown column' => ["email,admin\na@b.test,1", 'Unknown columns: admin'],
    'empty' => ['', 'no header row'],
]);

it('limits rows and size', function (): void {
    config()->set('roster.transfers.max_rows', 2);

    expect(fn () => reader("email\na@b.test\nb@b.test\nc@b.test")->validate())->toThrow(ValidationException::class, 'more than 2 rows');

    config()->set('roster.transfers.max_bytes', 10);

    expect(fn () => reader("email\na@b.test\n")->validate())->toThrow(ValidationException::class, 'larger than');
});

it('neutralizes spreadsheet formulas', function (?string $cell, string $safe): void {
    expect(Writer::safe($cell))->toBe($safe);
})->with([
    ['=1+1', "'=1+1"],
    ['+44 20', "'+44 20"],
    ['-2', "'-2"],
    ['@SUM(A1)', "'@SUM(A1)"],
    ["\tx", "'\tx"],
    ['plain', 'plain'],
    [null, ''],
]);
