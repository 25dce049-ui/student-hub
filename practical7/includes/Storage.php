<?php
/**
 * Practical 7 - File based storage (CSV + JSON).
 * All writes use LOCK_EX so concurrent submissions cannot corrupt the file.
 */

declare(strict_types=1);

final class Storage
{
    /** @return list<array<string,mixed>> */
    public static function readJson(): array
    {
        if (!is_file(JSON_FILE)) {
            return [];
        }

        $raw = file_get_contents(JSON_FILE);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string,mixed> $record
     * @throws RuntimeException when either file cannot be written
     */
    public static function append(array $record): void
    {
        $record['id'] = self::nextId();

        self::writeCsv($record);
        self::writeJson($record);
    }

    /** Append one row to the CSV file, creating the header on first run. */
    private static function writeCsv(array $record): void
    {
        $isNewFile = !is_file(CSV_FILE) || filesize(CSV_FILE) === 0;

        $handle = self::openForAppend(CSV_FILE);
        if ($isNewFile) {
            fputcsv($handle, CSV_HEADER);
        }

        $row = [];
        foreach (CSV_HEADER as $column) {
            $row[] = (string) ($record[$column] ?? '');
        }
        fputcsv($handle, $row);
        fclose($handle);
    }

    /** Append one record to the JSON array, re-writing the file atomically. */
    private static function writeJson(array $record): void
    {
        $records   = self::readJson();
        $records[] = $record;

        $json = json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('Unable to encode the record as JSON: ' . json_last_error_msg());
        }

        $temp = JSON_FILE . '.tmp';
        if (file_put_contents($temp, $json . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('Unable to write the temporary JSON file. Check folder permissions.');
        }
        if (!rename($temp, JSON_FILE)) {
            @unlink($temp);
            throw new RuntimeException('Unable to move the temporary file into place.');
        }
    }

    /** @return resource */
    private static function openForAppend(string $path)
    {
        $handle = @fopen($path, 'ab');
        if ($handle === false) {
            throw new RuntimeException('Unable to open "' . basename($path) . '" for writing. Check folder permissions.');
        }

        return $handle;
    }

    /** Auto-increment id based on the highest id already stored. */
    private static function nextId(): int
    {
        $max = 0;
        foreach (self::readJson() as $record) {
            $max = max($max, (int) ($record['id'] ?? 0));
        }

        return $max + 1;
    }

    /** @return list<array<string,mixed>> */
    public static function readCsv(): array
    {
        if (!is_file(CSV_FILE)) {
            return [];
        }

        $handle = @fopen(CSV_FILE, 'rb');
        if ($handle === false) {
            return [];
        }

        $rows   = [];
        $header = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === 1 && ($row[0] === null || $row[0] === '')) {
                continue;
            }
            $rows[] = array_combine($header ?: CSV_HEADER, array_pad($row, count($header ?: CSV_HEADER), ''));
        }
        fclose($handle);

        return $rows;
    }

    public static function count(): int
    {
        return count(self::readJson());
    }
}
