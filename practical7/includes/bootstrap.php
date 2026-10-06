<?php
/**
 * Practical 7 - Shared bootstrap. Loads config + helper classes once.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/Validator.php';
require_once __DIR__ . '/Storage.php';
require_once __DIR__ . '/Csrf.php';

/** Escape for safe HTML output. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Read one POST field as a trimmed string. */
function post(string $key): string
{
    return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : '';
}
