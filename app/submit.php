<?php

require_once 'config.php';
require_login();

$error = '';
$categories = [
    'Registration and admission',
    'Course access and registration',
    'Learning materials',
    'Examinations and results',
    'Fees and payments',
    'Technical support',
    'Academic support',
    'Student services',
    'General feedback',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $type = $_POST['type'] ?? '';
    $category = $_POST['category'] ?? '';
    $subject = trim($_POST['subject'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (
        !in_array($type, ['complaint', 'feedback'], true)
        || !in_array($category, $categories, true)
        || $subject === ''
        || $description === ''
    ) {
        $error = 'Complete all fields using the available selections.';
    } elseif (mb_strlen($subject) > 180 || mb_strlen($description) > 10000) {
        $error = 'The subject or description exceeds the permitted length.';
    } else {
        try {
            $reference = 'UIDLC-' . strtoupper(bin2hex(random_bytes(4)));
            $pdo->beginTransaction();

            $insert = $pdo->prepare(
                'INSERT INTO complaints
                 (reference_code, user_id, type, category, subject, description)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $insert->execute([
                $reference,
                (int) $_SESSION['user_id'],
                $type,
                $category,
                $subject,
                $description,
            ]);

            $complaintId = (int) $pdo->lastInsertId();
            $history = $pdo->prepare(
                "INSERT INTO status_history
                 (complaint_id, changed_by, old_status, new_status, note)
                 VALUES (?, ?, NULL, 'Submitted', 'Initial submission')"
            );
            $history->execute([$complaintId, (int) $_SESSION['user_id']]);

            $pdo->commit();
            header('Location: dashboard.php?submitted=' . urlencode($reference));
            exit;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('UIDLC submission error: ' . $exception->getMessage());
            $error = 'Your submission could not be saved. Please try again.';
        }
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New submission | UIDLC Feedback Desk</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="shell">
    <section class="panel">
        <a href="dashboard.php">← Dashboard</a>
        <h1>Submit a complaint or feedback</h1>

        <?php if ($error !== ''): ?>
            <p class="error"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

            <label>
                Submission type
                <select name="type" required>
                    <option value="">Select</option>
                    <option value="complaint">Complaint</option>
                    <option value="feedback">Feedback</option>
                </select>
            </label>

            <label>
                Category
                <select name="category" required>
                    <option value="">Select</option>
                    <?php foreach ($categories as $categoryOption): ?>
                        <option value="<?= e($categoryOption) ?>"><?= e($categoryOption) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Subject
                <input name="subject" maxlength="180" required>
            </label>

            <label>
                Description
                <textarea name="description" rows="7" maxlength="10000" required></textarea>
            </label>

            <button type="submit">Submit</button>
        </form>
    </section>
</main>
</body>
</html>
