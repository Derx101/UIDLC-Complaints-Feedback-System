<?php

require_once 'config.php';
require_login();

$complaintsQuery = $pdo->prepare(
    'SELECT * FROM complaints WHERE user_id = ? ORDER BY created_at DESC'
);
$complaintsQuery->execute([(int) $_SESSION['user_id']]);
$items = $complaintsQuery->fetchAll();

$historyQuery = $pdo->prepare(
    'SELECT h.*, c.reference_code
     FROM status_history h
     INNER JOIN complaints c ON c.id = h.complaint_id
     WHERE c.user_id = ?
     ORDER BY h.created_at DESC, h.id DESC'
);
$historyQuery->execute([(int) $_SESSION['user_id']]);
$historyByComplaint = [];
foreach ($historyQuery->fetchAll() as $event) {
    $historyByComplaint[$event['reference_code']][] = $event;
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student dashboard | UIDLC Feedback Desk</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="shell">
    <header class="top">
        <div>
            <strong>Feedback Desk</strong>
            <span>Student dashboard</span>
        </div>
        <a href="logout.php">Sign out</a>
    </header>

    <section class="panel">
        <p class="eyebrow">YOUR ACCOUNT</p>
        <h1>Hello, <?= e($_SESSION['name'] ?? 'Student') ?></h1>
        <p>Submit a new item or review the status and history of an existing submission.</p>
        <a class="button" href="submit.php">+ New complaint / feedback</a>
        <?php if (!empty($_GET['submitted'])): ?>
            <p class="success">
                Your submission was received. Reference: <strong><?= e($_GET['submitted']) ?></strong>
            </p>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>Your submissions</h2>
        <?php if (!$items): ?>
            <p>No submissions yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Type</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Administrator response</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= e($item['reference_code']) ?></td>
                            <td><?= e($item['type']) ?></td>
                            <td><?= e($item['subject']) ?></td>
                            <td><span class="badge"><?= e($item['status']) ?></span></td>
                            <td><?= e($item['created_at']) ?></td>
                            <td><?= nl2br(e($item['admin_response'] ?? 'Not yet provided')) ?></td>
                        </tr>
                        <tr class="history-row">
                            <td colspan="6">
                                <details class="history">
                                    <summary>View status history (<?= count($historyByComplaint[$item['reference_code']] ?? []) ?>)</summary>
                                    <?php $events = array_reverse($historyByComplaint[$item['reference_code']] ?? []); ?>
                                    <?php if (!$events): ?>
                                        <p>No status history is available yet.</p>
                                    <?php else: ?>
                                        <ol class="timeline">
                                            <?php foreach ($events as $event): ?>
                                                <li>
                                                    <strong><?= e($event['new_status']) ?></strong>
                                                    <span><?= e($event['created_at']) ?></span>
                                                    <?php if (!empty($event['note'])): ?>
                                                        <p><?= nl2br(e($event['note'])) ?></p>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ol>
                                    <?php endif; ?>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
