<?php

require_once 'config.php';
require_admin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $complaintId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $newStatus = $_POST['status'] ?? '';
    $response = trim($_POST['response'] ?? '');

    if (!$complaintId || !in_array($newStatus, status_labels(), true)) {
        $error = 'The requested update is invalid.';
    } elseif (mb_strlen($response) > 5000) {
        $error = 'The response must not exceed 5,000 characters.';
    } else {
        try {
            $pdo->beginTransaction();

            // Lock the complaint while the update and history record are written.
            $select = $pdo->prepare('SELECT status, admin_response FROM complaints WHERE id = ? FOR UPDATE');
            $select->execute([$complaintId]);
            $current = $select->fetch();

            if (!$current) {
                $pdo->rollBack();
                $error = 'The complaint could not be found.';
            } else {
                $oldStatus = $current['status'];
                $oldResponse = $current['admin_response'] ?? '';

                $update = $pdo->prepare(
                    'UPDATE complaints SET status = ?, admin_response = ? WHERE id = ?'
                );
                $update->execute([$newStatus, $response !== '' ? $response : null, $complaintId]);

                // Record a history event only when status or response actually changes.
                if ($oldStatus !== $newStatus || $oldResponse !== $response) {
                    $note = $response !== '' ? $response : 'Status updated by administrator.';
                    $history = $pdo->prepare(
                        'INSERT INTO status_history
                         (complaint_id, changed_by, old_status, new_status, note)
                         VALUES (?, ?, ?, ?, ?)'
                    );
                    $history->execute([
                        $complaintId,
                        (int) $_SESSION['user_id'],
                        $oldStatus,
                        $newStatus,
                        $note,
                    ]);
                }

                $pdo->commit();
                header('Location: admin.php?updated=1');
                exit;
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('UIDLC admin update error: ' . $exception->getMessage());
            $error = 'The update could not be saved. Please try again.';
        }
    }
}

if (isset($_GET['updated'])) {
    $success = 'The complaint record was updated successfully.';
}

$items = $pdo->query(
    'SELECT c.*, u.full_name, u.email
     FROM complaints c
     INNER JOIN users u ON u.id = c.user_id
     ORDER BY c.created_at DESC'
)->fetchAll();

$historyQuery = $pdo->query(
    'SELECT h.*, u.full_name AS changed_by_name
     FROM status_history h
     INNER JOIN users u ON u.id = h.changed_by
     ORDER BY h.created_at DESC, h.id DESC'
);
$historyByComplaint = [];
foreach ($historyQuery->fetchAll() as $event) {
    $historyByComplaint[$event['complaint_id']][] = $event;
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administrator dashboard | UIDLC Feedback Desk</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="shell">
    <header class="top">
        <div>
            <strong>Feedback Desk</strong>
            <span>Administrator</span>
        </div>
        <a href="logout.php">Sign out</a>
    </header>

    <section class="panel">
        <h1>Complaint and feedback queue</h1>
        <p><?= count($items) ?> submission(s).</p>

        <?php if ($success !== ''): ?>
            <p class="success"><?= e($success) ?></p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p class="error"><?= e($error) ?></p>
        <?php endif; ?>

        <?php if (!$items): ?>
            <p>No complaints or feedback have been submitted.</p>
        <?php endif; ?>

        <?php foreach ($items as $item): ?>
            <article class="ticket">
                <div class="ticket-head">
                    <strong>
                        <?= e($item['reference_code']) ?> · <?= e($item['subject']) ?>
                    </strong>
                    <span class="badge"><?= e($item['status']) ?></span>
                </div>

                <p>
                    <?= e($item['full_name']) ?> (<?= e($item['email']) ?>)
                    · <?= e($item['type']) ?> · <?= e($item['category']) ?>
                </p>
                <p><?= nl2br(e($item['description'])) ?></p>
                <p class="muted">Submitted: <?= e($item['created_at']) ?></p>

                <form method="post" class="admin-form" data-update-form>
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">

                    <label>
                        Status
                        <select name="status" required>
                            <?php foreach (status_labels() as $status): ?>
                                <option value="<?= e($status) ?>" <?= $status === $item['status'] ? 'selected' : '' ?>>
                                    <?= e($status) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        Response to student
                        <textarea name="response" rows="3" maxlength="5000"><?= e($item['admin_response']) ?></textarea>
                    </label>
                    <button type="submit">Save update</button>
                </form>

                <details class="history">
                    <summary>View status history (<?= count($historyByComplaint[$item['id']] ?? []) ?>)</summary>
                    <?php $events = array_reverse($historyByComplaint[$item['id']] ?? []); ?>
                    <?php if (!$events): ?>
                        <p>No history is available for this submission.</p>
                    <?php else: ?>
                        <ol class="timeline">
                            <?php foreach ($events as $event): ?>
                                <li>
                                    <strong><?= e($event['new_status']) ?></strong>
                                    <span><?= e($event['created_at']) ?> · <?= e($event['changed_by_name']) ?></span>
                                    <?php if (!empty($event['note'])): ?>
                                        <p><?= nl2br(e($event['note'])) ?></p>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </details>
            </article>
        <?php endforeach; ?>
    </section>
</main>
<script>
document.querySelectorAll('[data-update-form]').forEach((form) => {
    form.addEventListener('submit', () => {
        const button = form.querySelector('button[type="submit"]');
        if (button) {
            button.disabled = true;
            button.textContent = 'Saving...';
        }
    });
});
</script>
</body>
</html>
