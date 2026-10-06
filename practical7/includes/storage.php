<?php
/**
 * storage.php – Practical 7
 * Safe file-based storage helpers for CSV and JSON.
 *
 * Features:
 *  - Atomic writes using temp file + rename
 *  - File locking (LOCK_EX) to prevent race conditions
 *  - Append to CSV without loading entire file
 *  - Read/write full JSON array
 */

if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    http_response_code(403); exit('Access denied.');
}

/* ======================================================
   CSV STORAGE
   ====================================================== */

/**
 * Append one record (associative array) to a CSV file.
 * Writes the header row automatically on first write.
 *
 * @param  string $filepath   Absolute path to the CSV file
 * @param  array  $record     Associative array of field => value
 * @return bool               True on success
 */
function csv_append(string $filepath, array $record): bool {
    $isNew = !file_exists($filepath) || filesize($filepath) === 0;

    $fh = fopen($filepath, 'a');
    if (!$fh) return false;

    if (!flock($fh, LOCK_EX)) {
        fclose($fh);
        return false;
    }

    try {
        // Write header on first entry
        if ($isNew) {
            fputcsv($fh, array_keys($record));
        }
        fputcsv($fh, array_values($record));
        fflush($fh);
    } finally {
        flock($fh, LOCK_UN);
        fclose($fh);
    }

    return true;
}

/**
 * Read all records from a CSV file.
 * First row is treated as the header.
 *
 * @param  string $filepath
 * @return array  Array of associative arrays, or [] on error
 */
function csv_read_all(string $filepath): array {
    if (!file_exists($filepath) || filesize($filepath) === 0) return [];

    $fh = fopen($filepath, 'r');
    if (!$fh) return [];

    flock($fh, LOCK_SH);
    $records = [];
    $headers = null;

    while (($row = fgetcsv($fh)) !== false) {
        if ($headers === null) {
            $headers = $row;
            continue;
        }
        if (count($row) === count($headers)) {
            $records[] = array_combine($headers, $row);
        }
    }

    flock($fh, LOCK_UN);
    fclose($fh);
    return $records;
}

/* ======================================================
   JSON STORAGE
   ====================================================== */

/**
 * Append one record to a JSON array file.
 * Creates the file if it doesn't exist.
 *
 * @param  string $filepath
 * @param  array  $record
 * @return bool
 */
function json_append(string $filepath, array $record): bool {
    $records = json_read_all($filepath);
    $records[] = $record;
    return json_write_all($filepath, $records);
}

/**
 * Read all records from a JSON file.
 *
 * @param  string $filepath
 * @return array
 */
function json_read_all(string $filepath): array {
    if (!file_exists($filepath) || filesize($filepath) === 0) return [];

    $fh = fopen($filepath, 'r');
    if (!$fh) return [];

    flock($fh, LOCK_SH);
    $content = stream_get_contents($fh);
    flock($fh, LOCK_UN);
    fclose($fh);

    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

/**
 * Write (overwrite) an entire array to a JSON file atomically.
 *
 * @param  string $filepath
 * @param  array  $records
 * @return bool
 */
function json_write_all(string $filepath, array $records): bool {
    $dir     = dirname($filepath);
    $tmpFile = $dir . '/' . uniqid('.tmp_', true) . '.json';

    $json = json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) return false;

    // Write to temp file first (atomic pattern)
    $fh = fopen($tmpFile, 'w');
    if (!$fh) return false;

    flock($fh, LOCK_EX);
    fwrite($fh, $json);
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);

    // Rename is atomic on same filesystem
    return rename($tmpFile, $filepath);
}

/* ======================================================
   DATA DIRECTORY GUARD
   ====================================================== */

/**
 * Ensure the data directory exists and is writable.
 * Also create a .htaccess to block direct browser access.
 *
 * @param  string $dirPath
 * @return bool
 */
function ensure_data_dir(string $dirPath): bool {
    if (!is_dir($dirPath)) {
        if (!mkdir($dirPath, 0755, true)) return false;
    }

    $htaccess = $dirPath . '/.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Order deny,allow\nDeny from all\n");
    }

    return is_writable($dirPath);
}
