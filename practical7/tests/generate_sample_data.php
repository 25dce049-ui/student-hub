<?php
/**
 * Practical 7 - Generate a few sample records so the CSV/JSON files
 * can be submitted as part of the lab report.
 * Run: php tests/generate_sample_data.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$total = Storage::count();
if ($total > 0) {
    echo "Sample data already present ($total record(s)) - nothing to do.\n";
    exit(0);
}

$sample = [
    ['Aisha Khan',        'aisha.khan@example.com',   '9876543210', 'BCA', '2', 'Looking forward to the coding club activities.'],
    ['Rohit Sharma',      'rohit.sharma@example.com', '9812345678', 'BCS', '3', 'Please add me to the newsletter.'],
    ['Priya Menon',       'priya.menon@example.com',  '9900112233', 'MCA', '1', 'Interested in the internship cell.'],
    ['Daniel Fernandes', 'daniel.f@example.com',     '9700334455', 'BBA', '4', 'Requesting information about hostel facilities.'],
    ['Sneha Iyer',        'sneha.iyer@example.com',   '9655443322', 'BCom', '2', 'How can I join the photography society?'],
];

foreach ($sample as [$name, $email, $phone, $course, $year, $message]) {
    Storage::append([
        'submitted_at' => date('Y-m-d H:i:s'),
        'full_name'    => $name,
        'email'        => $email,
        'phone'        => $phone,
        'course'       => $course,
        'year'         => $year,
        'message'      => $message,
        'ip_address'   => '::1',
    ]);
}

echo 'Created ' . Storage::count() . " sample records in data/registrations.csv and data/registrations.json\n";
