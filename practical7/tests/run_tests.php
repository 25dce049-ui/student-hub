<?php
/**
 * Practical 7 - Automated test cases for validation, sanitisation and storage.
 * Run from the project root:  php tests/run_tests.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$passed = 0;
$failed = 0;

function check(string $label, bool $condition): void
{
    global $passed, $failed;

    if ($condition) {
        $passed++;
        echo "[PASS] $label\n";
    } else {
        $failed++;
        echo "[FAIL] $label\n";
    }
}

function valid(array $overrides = []): array
{
    return array_merge([
        'full_name' => 'Aisha Khan',
        'email'     => 'aisha.khan@example.com',
        'phone'     => '9876543210',
        'course'    => 'BCA',
        'year'      => '2',
        'message'   => 'I am interested in joining the student hub.',
    ], $overrides);
}

echo "=== Practical 7 - Sanitisation tests ===\n";
check('Trims surrounding whitespace', Validator::sanitizeText("  Aisha  ") === 'Aisha');
check('Strips HTML tags', Validator::sanitizeText('<script>alert(1)</script>Bob') === 'alert(1)Bob');
check('Removes null bytes', Validator::sanitizeText("Ais\0ha") === 'Aisha');
check('Collapses internal whitespace', Validator::sanitizeText("Aisha   Khan") === 'Aisha Khan');
check('Email lowercased', Validator::sanitizeEmail('  Aisha@Example.COM ') === 'aisha@example.com');
check('Multiline keeps newlines', str_contains(Validator::sanitizeMultiline("line1\n\n\n\nline2"), "\n\nline2"));

echo "\n=== Practical 7 - Validation tests ===\n";

$r = Validator::validate(valid());
check('Valid submission has no errors', $r['errors'] === []);

$r = Validator::validate(valid(['full_name' => '']));
check('Empty required name is rejected', isset($r['errors']['full_name']));

$r = Validator::validate(valid(['full_name' => 'Al']));
check('Name shorter than 3 chars is rejected', isset($r['errors']['full_name']));

$r = Validator::validate(valid(['full_name' => 'Bad<script>Name']));
check('Tags in name are sanitised away, not stored', $r['data']['full_name'] === 'BadName');
check('Sanitised name is then accepted', !isset($r['errors']['full_name']));

$r = Validator::validate(valid(['full_name' => 'Bad <b>Name</b>!']));
check('Name with illegal characters is rejected', isset($r['errors']['full_name']));

$r = Validator::validate(valid(['email' => 'not-an-email']));
check('Invalid email is rejected', isset($r['errors']['email']));

$r = Validator::validate(valid(['phone' => 'abc']));
check('Invalid phone is rejected', isset($r['errors']['phone']));

$r = Validator::validate(valid(['course' => 'HACKED']));
check('Course not in whitelist is rejected', isset($r['errors']['course']));

$r = Validator::validate(valid(['year' => '9']));
check('Year not in whitelist is rejected', isset($r['errors']['year']));

$r = Validator::validate(valid(['message' => 'short']));
check('Message under 10 chars is rejected', isset($r['errors']['message']));

$r = Validator::validate(valid(['message' => str_repeat('a', 501)]));
check('Message over 500 chars is rejected', isset($r['errors']['message']));

echo "\n=== Practical 7 - Storage tests ===\n";

$csvPath  = CSV_FILE;
$jsonPath = JSON_FILE;
$csvBackup  = is_file($csvPath) ? file_get_contents($csvPath) : null;
$jsonBackup = is_file($jsonPath) ? file_get_contents($jsonPath) : null;

@unlink($csvPath);
@unlink($jsonPath);

Storage::append(['submitted_at' => date('Y-m-d H:i:s'), 'full_name' => 'Test One', 'email' => 'one@example.com',
                 'phone' => '9000000001', 'course' => 'BCA', 'year' => '1', 'message' => 'First test record', 'ip_address' => '127.0.0.1']);
check('CSV file created on first write', is_file($csvPath));
check('JSON file created on first write', is_file($jsonPath));
check('First record has id 1', Storage::readJson()[0]['id'] === 1);

Storage::append(['submitted_at' => date('Y-m-d H:i:s'), 'full_name' => 'Test, Two "quoted"', 'email' => 'two@example.com',
                 'phone' => '9000000002', 'course' => 'BBA', 'year' => '3', 'message' => 'Second, with comma', 'ip_address' => '127.0.0.1']);
check('Second record has id 2', Storage::readJson()[1]['id'] === 2);
check('JSON count is 2', Storage::count() === 2);
check('CSV header written once', count(Storage::readCsv()) === 2);
check('CSV handles commas and quotes', Storage::readCsv()[1]['full_name'] === 'Test, Two "quoted"');
check('JSON is valid JSON', is_array(json_decode((string) file_get_contents($jsonPath), true)));

$before = count(Storage::readJson());
Validator::validate(valid(['email' => 'one@example.com']));
$r = Validator::validate(valid(['email' => 'one@example.com']));
check('Duplicate email is rejected', isset($r['errors']['email']));
check('Storage count unchanged after duplicate attempt', count(Storage::readJson()) === $before);

if ($csvBackup !== null) { file_put_contents($csvPath, $csvBackup); } else { @unlink($csvPath); }
if ($jsonBackup !== null) { file_put_contents($jsonPath, $jsonBackup); } else { @unlink($jsonPath); }

echo "\n=== Practical 7 - CSRF tests ===\n";
$_SESSION = [];
$token = Csrf::token();
check('Token is 64 hex chars', preg_match('/^[a-f0-9]{64}$/', $token) === 1);
check('Same token returned within a session', Csrf::token() === $token);
check('Wrong token is rejected', Csrf::validate('bad-token') === false);
check('Correct token is accepted', Csrf::validate($token) === true);
check('Token cannot be replayed', Csrf::validate($token) === false);

echo "\n----------------------------------------\n";
echo "Passed: $passed | Failed: $failed\n";
exit($failed === 0 ? 0 : 1);
