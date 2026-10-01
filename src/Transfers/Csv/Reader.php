<?php

declare(strict_types=1);

namespace JayI\Roster\Transfers\Csv;

use Generator;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Validation\ValidationException;
use SplFileObject;

/**
 * Reads an uploaded CSV as header-keyed rows.
 *
 * The header row names the columns (case-insensitive, trimmed); a UTF-8 BOM
 * is ignored. Rows are yielded with their 1-based line number and the byte
 * offset just after them, which a batch can resume from.
 */
final class Reader
{
    /**
     * @param  array{required: array<int, string>, optional: array<int, string>}  $columns
     */
    public function __construct(
        private readonly Filesystem $disk,
        private readonly string $path,
        private readonly array $columns,
    ) {}

    /**
     * Check the file's size, header and row count before anything else.
     */
    public function validate(): int
    {
        $size = $this->disk->size($this->path);

        if ($size > (int) config('roster.transfers.max_bytes', 5242880)) {
            throw ValidationException::withMessages(['file' => __('roster::roster.csv_too_large')]);
        }

        $this->header();

        $count = 0;

        foreach ($this->rows() as $ignored) {
            if (++$count > (int) config('roster.transfers.max_rows', 10000)) {
                throw ValidationException::withMessages(['file' => __('roster::roster.csv_too_many_rows', ['max' => config('roster.transfers.max_rows', 10000)])]);
            }
        }

        return $count;
    }

    /**
     * @return array<int, string>
     */
    public function header(): array
    {
        $file = $this->open();
        $header = $file->fgetcsv(escape: '\\');

        if (! is_array($header) || $header === [null]) {
            throw ValidationException::withMessages(['file' => __('roster::roster.csv_no_header')]);
        }

        $header = array_map(fn (mixed $cell): string => strtolower(trim((string) $cell)), $header);
        $header[0] = (string) preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);

        if (in_array('password', $header, true)) {
            throw ValidationException::withMessages(['file' => __('roster::roster.csv_no_passwords')]);
        }

        $missing = array_diff($this->columns['required'], $header);

        if ($missing !== []) {
            throw ValidationException::withMessages(['file' => __('roster::roster.csv_missing_columns', ['columns' => implode(', ', $missing)])]);
        }

        $unknown = array_diff(array_filter($header), [...$this->columns['required'], ...$this->columns['optional']]);

        if ($unknown !== []) {
            throw ValidationException::withMessages(['file' => __('roster::roster.csv_unknown_columns', ['columns' => implode(', ', $unknown)])]);
        }

        return $header;
    }

    /**
     * Rows after `$offset` (a byte position from a previous read).
     *
     * @return Generator<int, array{line: int, offset: int, values: array<string, string>}>
     */
    public function rows(int $offset = 0, int $startLine = 2): Generator
    {
        $header = $this->header();
        $file = $this->open();
        $line = 1;

        if ($offset > 0) {
            $file->fseek($offset);
            $line = $startLine - 1;
        } else {
            $file->fgetcsv(escape: '\\');
        }

        while (! $file->eof()) {
            $cells = $file->fgetcsv(escape: '\\');
            $line++;

            if (! is_array($cells) || $cells === [null]) {
                continue;
            }

            $values = [];

            foreach ($header as $index => $column) {
                if ($column !== '') {
                    $values[$column] = trim((string) ($cells[$index] ?? ''));
                }
            }

            yield ['line' => $line, 'offset' => (int) $file->ftell(), 'values' => $values];
        }
    }

    private function open(): SplFileObject
    {
        $stream = $this->disk->path($this->path);

        return new SplFileObject($stream, 'r');
    }
}
