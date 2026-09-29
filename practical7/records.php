<?php
/**
 * Practical 7 (Intermediate extension) - Display the stored CSV/JSON records.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$format  = ($_GET['format'] ?? 'json') === 'csv' ? 'csv' : 'json';
$records = $format === 'csv' ? Storage::readCsv() : Storage::readJson();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stored Records - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<main class="card wide">
    <h1>Stored Records</h1>
    <p class="lead">Records read back from <code>data/registrations.<?= e($format) ?></code>.</p>

    <div class="actions">
        <a class="btn<?= $format === 'json' ? ' btn-primary' : '' ?>" href="records.php?format=json">JSON</a>
        <a class="btn<?= $format === 'csv' ? ' btn-primary' : '' ?>" href="records.php?format=csv">CSV</a>
        <a class="btn" href="index.php">Back to form</a>
    </div>

    <?php if ($records === []): ?>
        <div class="alert alert-info">No records stored yet. <a href="index.php">Submit the form</a> first.</div>
    <?php else: ?>
        <p class="meta"><?= e((string) count($records)) ?> record(s) found.</p>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Submitted at</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Course</th>
                        <th>Year</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $record): ?>
                        <tr>
                            <td><?= e((string) ($record['id'] ?? '')) ?></td>
                            <td><?= e((string) ($record['submitted_at'] ?? '')) ?></td>
                            <td><?= e((string) ($record['full_name'] ?? '')) ?></td>
                            <td><?= e((string) ($record['email'] ?? '')) ?></td>
                            <td><?= e((string) ($record['phone'] ?? '')) ?></td>
                            <td><?= e((string) ($record['course'] ?? '')) ?></td>
                            <td><?= e((string) ($record['year'] ?? '')) ?></td>
                            <td><?= e((string) ($record['message'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
