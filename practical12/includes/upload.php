<?php
/**
 * Practical 12 – Event Management CRUD with Poster Upload
 * includes/upload.php  –  Secure file upload helper
 *
 * Security practices:
 *  ✅ Validates MIME type (finfo, not just extension)
 *  ✅ Validates file extension against allow-list
 *  ✅ Enforces max file size (server-side, independent of php.ini)
 *  ✅ Renames file to a UUID to prevent directory traversal / name collisions
 *  ✅ Stores outside webroot pattern: uploads/ has .htaccess to block direct PHP execution
 *  ✅ Returns structured result array (success, filename, error)
 */

require_once __DIR__ . '/config.php';

/**
 * Handle poster upload from $_FILES['poster'].
 *
 * @param  array       $file         Entry from $_FILES
 * @param  string|null $oldFilename  Previous poster to delete on success
 * @return array{ok: bool, filename: ?string, error: ?string}
 */
function upload_poster(array $file, ?string $oldFilename = null): array
{
    // No file selected
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'filename' => null, 'error' => null];
    }

    // Upload error codes
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $msgs = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form upload limit.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary upload folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'Upload blocked by server extension.',
        ];
        return ['ok' => false, 'filename' => null,
                'error' => $msgs[$file['error']] ?? 'Unknown upload error.'];
    }

    // Size validation
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['ok' => false, 'filename' => null,
                'error' => 'File size exceeds the 2 MB limit. Got ' .
                           round($file['size'] / 1024 / 1024, 2) . ' MB.'];
    }

    // Extension validation
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTS, true)) {
        return ['ok' => false, 'filename' => null,
                'error' => 'Invalid file type. Allowed: ' . implode(', ', ALLOWED_EXTS) . '.'];
    }

    // MIME type validation via finfo (server-side, cannot be spoofed)
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, ALLOWED_TYPES, true)) {
        return ['ok' => false, 'filename' => null,
                'error' => 'File content does not match an allowed image type (' . $mimeType . ').'];
    }

    // Ensure upload directory exists
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
        // Drop .htaccess to block direct PHP execution (Advanced Extension)
        file_put_contents(UPLOAD_DIR . '.htaccess',
            "Options -Indexes\n<Files *.php>\n  Deny from all\n</Files>\n");
    }

    // Generate unique filename (Advanced Extension: image renaming)
    $newFilename = sprintf('%s_%s.%s', date('Ymd_His'), bin2hex(random_bytes(6)), $ext);
    $destination = UPLOAD_DIR . $newFilename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['ok' => false, 'filename' => null, 'error' => 'Failed to save uploaded file.'];
    }

    // Delete old poster if replacing
    if ($oldFilename && file_exists(UPLOAD_DIR . $oldFilename)) {
        @unlink(UPLOAD_DIR . $oldFilename);
    }

    return ['ok' => true, 'filename' => $newFilename, 'error' => null];
}

/**
 * Delete a poster file from disk.
 */
function delete_poster(?string $filename): void
{
    if ($filename && file_exists(UPLOAD_DIR . $filename)) {
        @unlink(UPLOAD_DIR . $filename);
    }
}
