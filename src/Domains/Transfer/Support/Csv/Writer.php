<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Support\Csv;

use RuntimeException;

/**
 * Streams CSV rows to a file, neutralizing spreadsheet formulas: a cell that
 * starts with = + - @ (or a tab or carriage return) gets a leading quote, so
 * opening an export never runs anything.
 */
final class Writer
{
    /** @var resource */
    private $handle;

    public function __construct(string $absolutePath, bool $append = false)
    {
        $handle = fopen($absolutePath, $append ? 'ab' : 'wb');

        if ($handle === false) {
            throw new RuntimeException("Cannot write [{$absolutePath}].");
        }

        $this->handle = $handle;
    }

    /**
     * @param  array<int, mixed>  $cells
     */
    public function write(array $cells): void
    {
        fputcsv($this->handle, array_map(fn (mixed $cell): string => self::safe($cell), $cells), escape: '\\');
    }

    public function close(): void
    {
        fclose($this->handle);
    }

    public static function safe(mixed $cell): string
    {
        $value = match (true) {
            is_bool($cell) => $cell ? 'true' : 'false',
            is_scalar($cell) => (string) $cell,
            $cell === null => '',
            default => (string) json_encode($cell),
        };

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }
}
