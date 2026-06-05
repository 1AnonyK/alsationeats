<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
  header('Location: /index.php');
  exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $pass  = $_POST['password'] ?? '';
  if (loginUser($email, $pass)) {
    $redirect = $_GET['next'] ?? '/index.php';
    header("Location: $redirect");
    exit;
  }
  $error = 'Incorrect email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Log In — Alsation Eats</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
  <link href="/assets/css/style.css" rel="stylesheet">
</head>

<body>
  <div class="auth-wrapper">
    <div class="auth-card ae-form">
      <span class="auth-logo">Alsation Eats</span>
      <p class="auth-sub">Log in to order from your favourite spots</p>

      <?php if ($error): ?>
        <div class="ae-alert ae-alert-danger"><?= h($error) ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Email address</label>
          <input type="email" name="email" class="form-control" required
            value="<?= h($_POST['email'] ?? '') ?>" placeholder="you@example.com">
        </div>
        <div class="mb-4">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required placeholder="••••••••">
        </div>
        <button type="submit" class="btn-teal w-100">Log In</button>
      </form>

      <p class="text-center mt-4 mb-0" style="font-size:.9rem;color:var(--text-mid)">
        Don't have an account? <a href="/register.php">Sign up</a>
      </p>
    </div>
  </div>
</body>

</html>