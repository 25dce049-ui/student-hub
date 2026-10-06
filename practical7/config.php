<?php
/**
 * Practical 7 - Configuration
 * Central place for storage paths, field rules and app settings.
 */

declare(strict_types=1);

const APP_NAME        = 'Student Hub - Registration';
const DATA_DIR        = __DIR__ . '/data';
const CSV_FILE        = DATA_DIR . '/registrations.csv';
const JSON_FILE       = DATA_DIR . '/registrations.json';
const CSV_HEADER      = ['id', 'submitted_at', 'full_name', 'email', 'phone', 'course', 'year', 'message', 'ip_address'];

/** Field rules used for both validation and sanitisation. */
const FIELD_RULES = [
    'full_name' => ['required' => true,  'min' => 3, 'max' => 60,  'label' => 'Full name'],
    'email'     => ['required' => true,  'max' => 120,            'label' => 'Email'],
    'phone'     => ['required' => true,  'min' => 7, 'max' => 15,  'label' => 'Phone number'],
    'course'    => ['required' => true,  'max' => 60,             'label' => 'Course'],
    'year'      => ['required' => true,                        'label' => 'Year of study'],
    'message'   => ['required' => false, 'min' => 10, 'max' => 500, 'label' => 'Message'],
];

/** Allowed values for select inputs (checked again on the server). */
const ALLOWED_COURSES = ['BCA', 'BBA', 'BCS', 'BCom', 'MCA', 'MSc'];
const ALLOWED_YEARS   = ['1', '2', '3', '4', '5'];

ensure_data_dir();

/** Create the storage directory (and .htaccess guard) if it does not exist. */
function ensure_data_dir(): void
{
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0775, true);
    }

    $htaccess = DATA_DIR . '/.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents(
            $htaccess,
            "# Practical 7 - deny direct web access to stored records\n"
            . "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
            . "<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n"
        );
    }
}
