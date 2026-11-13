<?php

require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (
        $name === ''
        || mb_strlen($name) > 120
        || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || strlen($email) > 190
        || strlen($password) < 10
        || strlen($password) > 1024
    ) {
        $error = 'Enter a name (maximum 120 characters), a valid email address and a password between 10 and 1,024 characters.';
    } else {
        try {
            $statement = $pdo->prepare(
                'INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)'
            );
            $statement->execute([
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
            ]);

            header('Location: login.php?registered=1');
            exit;
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $error = 'This email address is already registered.';
            } else {
                error_log('UIDLC registration error: ' . $exception->getMessage());
                $error = 'The account could not be created. Please try again.';
            }
        }
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="shell">
    <div class="panel narrow">
        <a href="index.php">← Home</a>
        <h1>Create account</h1>

        <?php if ($error !== ''): ?>
            <p class="error"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <label>
                Full name
                <input name="full_name" required maxlength="120" autocomplete="name">
            </label>
            <label>
                Email
                <input type="email" name="email" required autocomplete="email">
            </label>
            <label>
                Password (minimum 10 characters)
                <input type="password" name="password" minlength="10" maxlength="1024" required autocomplete="new-password">
            </label>
            <button type="submit">Create account</button>
        </form>

        <p>Already registered? <a href="login.php">Sign in</a></p>
    </div>
</main>
</body>
</html>
