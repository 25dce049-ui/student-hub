<?php
/**
 * Practical 7 - Registration form.
 * Displays success messages flashed from process.php and the input form itself.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

$errors  = $_SESSION['flash_errors'] ?? [];
$old     = $_SESSION['flash_old'] ?? [];
$hasData = isset($_SESSION['flash_success']) || $errors !== [];
unset($_SESSION['flash_errors'], $_SESSION['flash_old']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<main class="card">
    <h1>Student Registration</h1>
    <p class="lead">Practical 7 - PHP form processing with server-side validation and CSV/JSON file storage.</p>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success" role="status"><strong>Success!</strong> <?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($errors !== []): ?>
        <div class="alert alert-error" role="alert">
            <strong>Error!</strong> Please fix the <?= count($errors) === 1 ? 'field' : 'fields' ?> below.
        </div>
    <?php endif; ?>

    <form id="form" method="post" action="process.php" novalidate>
        <?= Csrf::field() ?>

        <div class="field<?= isset($errors['full_name']) ? ' has-error' : '' ?>">
            <label for="full_name">Full name <span class="req">*</span></label>
            <input type="text" id="full_name" name="full_name" maxlength="60" required
                   value="<?= e($old['full_name'] ?? '') ?>">
            <?php if (isset($errors['full_name'])): ?><p class="error"><?= e($errors['full_name']) ?></p><?php endif; ?>
        </div>

        <div class="field<?= isset($errors['email']) ? ' has-error' : '' ?>">
            <label for="email">Email <span class="req">*</span></label>
            <input type="email" id="email" name="email" maxlength="120" required
                   value="<?= e($old['email'] ?? '') ?>">
            <?php if (isset($errors['email'])): ?><p class="error"><?= e($errors['email']) ?></p><?php endif; ?>
        </div>

        <div class="field<?= isset($errors['phone']) ? ' has-error' : '' ?>">
            <label for="phone">Phone number <span class="req">*</span></label>
            <input type="text" id="phone" name="phone" maxlength="15" required
                   value="<?= e($old['phone'] ?? '') ?>">
            <?php if (isset($errors['phone'])): ?><p class="error"><?= e($errors['phone']) ?></p><?php endif; ?>
        </div>

        <div class="row">
            <div class="field<?= isset($errors['course']) ? ' has-error' : '' ?>">
                <label for="course">Course <span class="req">*</span></label>
                <select id="course" name="course" required>
                    <option value="">-- Select course --</option>
                    <?php foreach (ALLOWED_COURSES as $course): ?>
                        <option value="<?= e($course) ?>"<?= ($old['course'] ?? '') === $course ? ' selected' : '' ?>><?= e($course) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['course'])): ?><p class="error"><?= e($errors['course']) ?></p><?php endif; ?>
            </div>

            <div class="field<?= isset($errors['year']) ? ' has-error' : '' ?>">
                <label for="year">Year of study <span class="req">*</span></label>
                <select id="year" name="year" required>
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
            <textarea id="message" name="message" rows="4" maxlength="500"
                      placeholder="Anything you would like to add"><?= e($old['message'] ?? '') ?></textarea>
            <?php if (isset($errors['message'])): ?><p class="error"><?= e($errors['message']) ?></p><?php endif; ?>
        </div>

        <div class="actions">
            <button type="submit" class="btn btn-primary">Submit registration</button>
            <a class="btn" href="records.php">View stored records (<?= e((string) Storage::count()) ?>)</a>
        </div>
    </form>
</main>
</body>
</html>
