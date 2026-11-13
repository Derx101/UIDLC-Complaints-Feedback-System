<?php

require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $statement = $pdo->prepare(
        'SELECT id, full_name, password_hash, role FROM users WHERE email = ?'
    );
    $statement->execute([$email]);
    $user = $statement->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

        header('Location: ' . ($user['role'] === 'admin' ? 'admin.php' : 'dashboard.php'));
        exit;
    }

    $error = 'Invalid email or password.';
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="shell">
    <div class="panel narrow">
        <a href="index.php">← Home</a>
        <h1>Welcome back</h1>

        <?php if (isset($_GET['registered'])): ?>
            <p class="success">Account created. Please sign in.</p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <p class="error"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <label>
                Email
                <input type="email" name="email" required autocomplete="username">
            </label>
            <label>
                Password
                <input type="password" name="password" required autocomplete="current-password">
            </label>
            <button type="submit">Sign in</button>
        </form>

        <p>New student? <a href="register.php">Create account</a></p>
    </div>
</main>
</body>
</html>
