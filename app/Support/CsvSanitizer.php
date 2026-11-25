<?php

declare(strict_types=1);

namespace App\Support;

final class CsvSanitizer
{
    /**
     * Prevent CSV/Spreadsheet formula injection by prefixing trigger characters with a single quote.
     */
    public static function escapeCell(mixed $value): string
    {
        $string = (string) ($value ?? '');

        if ($string !== '' && preg_match('/^[=+\-@\t\r]/', $string) === 1) {
            return "'".$string;
        }

        return $string;
    }

    /**
     * Sanitize an entire CSV row.
     *
     * @param  array<int, mixed>  $row
     * @return array<int, string>
     */
    public static function escapeRow(array $row): array
    {
        return array_map(static fn (mixed $cell): string => self::escapeCell($cell), $row);
    }
}
