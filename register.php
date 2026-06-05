<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) { header('Location: /index.php'); exit; }

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if (strlen($name) < 2) $error = 'Please enter your full name.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Please enter a valid email address.';
    elseif (strlen($pass) < 8) $error = 'Password must be at least 8 characters.';
    elseif ($pass !== $pass2) $error = 'Passwords do not match.';
    else {
        $uid = registerUser($name, $email, $pass, $phone);
        if ($uid) {
            loginUser($email, $pass);
            header('Location: /index.php'); exit;
        } else {
            $error = 'That email is already registered. <a href="/login.php">Log in instead?</a>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Sign Up — Alsation Eats</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
<link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card ae-form">
    <span class="auth-logo">Alsation Eats</span>
    <p class="auth-sub">Join the township food revolution 🍖</p>

    <?php if ($error): ?>
    <div class="ae-alert ae-alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Full Name</label>
        <input type="text" name="name" class="form-control" required
               value="<?= h($_POST['name'] ?? '') ?>" placeholder="Your full name">
      </div>
      <div class="mb-3">
        <label class="form-label">Email address</label>
        <input type="email" name="email" class="form-control" required
               value="<?= h($_POST['email'] ?? '') ?>" placeholder="you@example.com">
      </div>
      <div class="mb-3">
        <label class="form-label">Phone (optional)</label>
        <input type="tel" name="phone" class="form-control"
               value="<?= h($_POST['phone'] ?? '') ?>" placeholder="e.g. 083 123 4567">
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required placeholder="Min 8 characters">
      </div>
      <div class="mb-4">
        <label class="form-label">Confirm Password</label>
        <input type="password" name="password2" class="form-control" required placeholder="Repeat password">
      </div>
      <button type="submit" class="btn-teal w-100">Create Account</button>
    </form>

    <div class="ae-alert ae-alert-info mt-4" style="font-size:.82rem">
      <i class="fa-solid fa-store"></i>
      <strong>Want to sell too?</strong> After signing up, visit your profile to open your own shop on Alsation Eats.
    </div>

    <p class="text-center mb-0" style="font-size:.9rem;color:var(--text-mid)">
      Already have an account? <a href="/login.php">Log in</a>
    </p>
  </div>
</div>
</body>
</html>
