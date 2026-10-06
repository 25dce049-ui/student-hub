<?php
/**
 * Practical 7 - Form processor.
 * Receives the POST submission, validates it server side, and stores the
 * record in data/registrations.csv and data/registrations.json.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$errors  = [];
$old     = [];
$success = false;

/* Only POST is accepted - anything else is rejected outright. */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    $errors['_form'] = 'This form only accepts POST submissions. Please use the form below.';
} else {
    /* Advanced extension: CSRF protection. */
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        $errors['_form'] = 'Your form session expired or the security token was invalid. Please try again.';
    } else {
        try {
            $result  = Validator::validate($_POST);
            $errors  = $result['errors'];
            $old     = $result['data'];

            if ($errors === []) {
                Storage::append([
                    'submitted_at' => date('Y-m-d H:i:s'),
                    'full_name'   => $old['full_name'],
                    'email'       => $old['email'],
                    'phone'       => $old['phone'],
                    'course'      => $old['course'],
                    'year'        => $old['year'],
                    'message'     => $old['message'],
                    'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                ]);

                $success = true;
                $old     = [];
            }
        } catch (RuntimeException $e) {
            $errors['_form'] = 'Your submission could not be saved: ' . $e->getMessage();
        }
    }
}

$records = Storage::readJson();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<main class="card">
    <h1>Submission Result</h1>

    <?php if ($success): ?>
        <div class="alert alert-success" role="status">
            <strong>Success!</strong>
            Thank you, <?= e($old === [] ? 'there' : 'registrant') ?>. Your registration was saved to
            <code>registrations.csv</code> and <code>registrations.json</code>.
        </div>
        <dl class="summary">
            <dt>Stored record ID</dt><dd><?= e((string) Storage::count()) ?></dd>
            <dt>Total records now</dt><dd><?= e((string) count($records)) ?></dd>
        </dl>
        <div class="actions">
            <a class="btn btn-primary" href="index.php">Register another student</a>
            <a class="btn" href="records.php">View all records</a>
        </div>
    <?php else: ?>
        <div class="alert alert-error" role="alert">
            <strong>Error!</strong>
            <?= count($errors) === 1 ? '1 problem was' : count($errors) . ' problems were' ?> found.
            Please review the highlighted fields.
        </div>

        <ul class="error-summary">
            <?php foreach ($errors as $field => $message): ?>
                <li><a href="#form"><?= e($message) ?></a></li>
            <?php endforeach; ?>
        </ul>

        <form id="form" method="post" action="process.php" novalidate>
            <?= Csrf::field() ?>

            <div class="field<?= isset($errors['full_name']) ? ' has-error' : '' ?>">
                <label for="full_name">Full name <span class="req">*</span></label>
                <input type="text" id="full_name" name="full_name" maxlength="60"
                       value="<?= e($old['full_name'] ?? '') ?>">
                <?php if (isset($errors['full_name'])): ?><p class="error"><?= e($errors['full_name']) ?></p><?php endif; ?>
            </div>

            <div class="field<?= isset($errors['email']) ? ' has-error' : '' ?>">
                <label for="email">Email <span class="req">*</span></label>
                <input type="email" id="email" name="email" maxlength="120"
                       value="<?= e($old['email'] ?? '') ?>">
                <?php if (isset($errors['email'])): ?><p class="error"><?= e($errors['email']) ?></p><?php endif; ?>
            </div>

            <div class="field<?= isset($errors['phone']) ? ' has-error' : '' ?>">
                <label for="phone">Phone number <span class="req">*</span></label>
                <input type="text" id="phone" name="phone" maxlength="15"
                       value="<?= e($old['phone'] ?? '') ?>">
                <?php if (isset($errors['phone'])): ?><p class="error"><?= e($errors['phone']) ?></p><?php endif; ?>
            </div>

            <div class="row">
                <div class="field<?= isset($errors['course']) ? ' has-error' : '' ?>">
                    <label for="course">Course <span class="req">*</span></label>
                    <select id="course" name="course">
                        <option value="">-- Select course --</option>
                        <?php foreach (ALLOWED_COURSES as $course): ?>
                            <option value="<?= e($course) ?>"<?= ($old['course'] ?? '') === $course ? ' selected' : '' ?>><?= e($course) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['course'])): ?><p class="error"><?= e($errors['course']) ?></p><?php endif; ?>
                </div>

                <div class="field<?= isset($errors['year']) ? ' has-error' : '' ?>">
                    <label for="year">Year of study <span class="req">*</span></label>
                    <select id="year" name="year">
                        <option value="">-- Select year --</option>
                        <?php foreach (ALLOWED_YEARS as $year): ?>
                            <option value="<?= e($year) ?>"<?= ($old['year'] ?? '') === $year ? ' selected' : '' ?>><?= e($year) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['year'])): ?><p class="error"><?= e($errors['year']) ?></p><?php endif; ?>
                </div>
            </div>

            <div class="field<?= isset($errors['message']) ? ' has-error' : '' ?>">
                <label for="message">Message (optional)</label>
                <textarea id="message" name="message" rows="4" maxlength="500"><?= e($old['message'] ?? '') ?></textarea>
                <?php if (isset($errors['message'])): ?><p class="error"><?= e($errors['message']) ?></p><?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">Submit registration</button>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
