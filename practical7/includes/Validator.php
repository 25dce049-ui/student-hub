<?php
/**
 * Practical 7 - Sanitisation + server side validation helpers.
 * Never trust anything that arrives in $_POST.
 */

declare(strict_types=1);

final class Validator
{
    /**
     * Convert arbitrary user input into a trimmed, tag-free, safe-to-store string.
     */
    public static function sanitizeText(string $value): string
    {
        // Strip NULL bytes and control characters that can corrupt CSV/JSON output.
        $value = str_replace("\0", '', $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? '';

        // Trim whitespace from both ends.
        $value = trim($value);

        // Remove any HTML/PHP tags (defence against stored XSS).
        $value = strip_tags($value);

        // Decode HTML entities so "&lt;b&gt;" is caught by strip_tags too.
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = strip_tags($value);

        // Collapse runs of whitespace into a single space.
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return $value;
    }

    /** Free-text area: keep newlines, drop everything else. */
    public static function sanitizeMultiline(string $value): string
    {
        $value = str_replace("\0", '', $value);
        $value = strip_tags($value);
        $value = preg_replace('/[ \t]+/u', ' ', $value) ?? '';
        $value = preg_replace('/\r\n|\r|\n/u', "\n", $value) ?? '';
        $value = preg_replace('/\n{3,}/u', "\n\n", $value) ?? '';

        return trim($value);
    }

    /** Emails: lowercase and trimmed, validated with filter_var. */
    public static function sanitizeEmail(string $value): string
    {
        return strtolower(trim(str_replace(' ', '', $value)));
    }

    /**
     * Validate the raw POST array.
     *
     * @param  array<string,mixed> $input
     * @return array{data:array<string,string>, errors:array<string,string>}
     */
    public static function validate(array $input): array
    {
        $data    = [];
        $errors  = [];

        foreach (FIELD_RULES as $field => $rule) {
            $raw = isset($input[$field]) && is_string($input[$field]) ? $input[$field] : '';
            $raw = trim($raw);

            if ($raw === '') {
                if (!empty($rule['required'])) {
                    $errors[$field] = $rule['label'] . ' is required.';
                }
                $data[$field] = '';
                continue;
            }

            $isMultiline = $field === 'message';
            $value = $isMultiline
                ? self::sanitizeMultiline($raw)
                : self::sanitizeText($raw);

            if ($field === 'email') {
                $value = self::sanitizeEmail($raw);
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = 'Enter a valid email address.';
                }
            } elseif ($field === 'phone') {
                if (!preg_match('/^[0-9+()\- ]{7,15}$/', $value)) {
                    $errors[$field] = 'Phone number may only contain digits, spaces, +, - or ().';
                }
            } elseif ($field === 'full_name') {
                if (!preg_match("/^[\p{L}\p{M}][\p{L}\p{M} .'-]*$/u", $value)) {
                    $errors[$field] = 'Name may only contain letters, spaces, apostrophes, hyphens and dots.';
                }
            } elseif ($field === 'course') {
                if (!in_array($value, ALLOWED_COURSES, true)) {
                    $errors[$field] = 'Select a valid course.';
                }
            } elseif ($field === 'year') {
                if (!in_array($value, ALLOWED_YEARS, true)) {
                    $errors[$field] = 'Select a valid year of study.';
                }
            }

            if (isset($rule['min']) && $value !== '' && mb_strlen($value) < $rule['min']) {
                $errors[$field] = $rule['label'] . ' must be at least ' . $rule['min'] . ' characters.';
            }
            if (isset($rule['max']) && mb_strlen($value) > $rule['max']) {
                $errors[$field] = $rule['label'] . ' must not exceed ' . $rule['max'] . ' characters.';
            }

            $data[$field] = $value;
        }

        if (!isset($errors['email']) && ($data['email'] ?? '') !== '' && self::isDuplicateEmail($data['email'])) {
            $errors['email'] = 'This email address is already registered.';
        }

        return ['data' => $data, 'errors' => $errors];
    }

    /** Check the email against previously stored records. */
    private static function isDuplicateEmail(string $email): bool
    {
        foreach (Storage::readJson() as $record) {
            if (strcasecmp((string) ($record['email'] ?? ''), $email) === 0) {
                return true;
            }
        }

        return false;
    }
}
