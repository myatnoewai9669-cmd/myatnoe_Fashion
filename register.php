<?php
require 'db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Already logged in -> go home
if (!empty($_SESSION['user_id'])) { header('Location: index.php'); exit; }

$err = '';
$name = $email = $phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name    = trim($_POST['full_name'] ?? '');
  $email   = strtolower(trim($_POST['email'] ?? ''));
  $phone   = trim($_POST['phone'] ?? '');
  $pass    = $_POST['password'] ?? '';
  $confirm = $_POST['confirm'] ?? '';

  if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $err = 'Please enter a valid name and email.';
  } elseif ($phone !== '' && !preg_match('/^[0-9+\-\s()]{6,20}$/', $phone)) {
    $err = 'Please enter a valid phone number.';
  } elseif (strlen($pass) < 6) {
    $err = 'Password must be at least 6 characters.';
  } elseif ($pass !== $confirm) {
    $err = 'Passwords do not match.';
  } else {
    $st = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $st->execute([$email]);
    if ($st->fetch()) {
      $err = 'This email is already registered.';
    } else {
      $pdo->prepare("INSERT INTO users (full_name,email,phone,password_hash) VALUES (?,?,?,?)")
          ->execute([$name, $email, $phone, password_hash($pass, PASSWORD_DEFAULT)]);

      session_regenerate_id(true);
      $_SESSION['user_id']   = $pdo->lastInsertId();
      $_SESSION['user_name'] = $name;
      $_SESSION['role']      = 'customer';
      header('Location: index.php'); exit;
    }
  }
}

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign Up - Myat Noe Fashion</title>
<link rel="stylesheet" href="auth.css">
</head>
<body>
<div class="sp">
  <div class="sp-left">
    <a class="sp-logo" href="index.php">Myat Noe Fashion</a>
    <p style="margin-top:20px">Join our fashion community and be the first to know about new arrivals, exclusive offers and more!</p>
    <div class="rack"><img src="auth-bg.jpg.jpeg" alt="Myat Noe Fashion"></div>
  </div>

  <div class="sp-right">
    <form method="post" class="sp-form" autocomplete="on">
      <h2>Create Account</h2>
      <?php if ($err): ?><p class="err"><?= e($err) ?></p><?php endif; ?>

      <label for="full_name">Full Name</label>
      <div class="in"><span>&#128100;</span>
        <input id="full_name" name="full_name" value="<?= e($name) ?>" placeholder="Enter your full name" autocomplete="name" required>
      </div>

      <label for="email">Email Address</label>
      <div class="in"><span>&#9993;</span>
        <input id="email" type="email" name="email" value="<?= e($email) ?>" placeholder="Enter your email" autocomplete="email" required>
      </div>

      <label for="phone">Phone Number</label>
      <div class="in"><span>&#128222;</span>
        <input id="phone" type="tel" name="phone" value="<?= e($phone) ?>" placeholder="Enter your phone number" autocomplete="tel">
      </div>

      <label for="password">Password</label>
      <div class="in"><span>&#128274;</span>
        <input id="password" type="password" name="password" placeholder="Create a password" minlength="6" autocomplete="new-password" required>
      </div>

      <label for="confirm">Confirm Password</label>
      <div class="in"><span>&#128274;</span>
        <input id="confirm" type="password" name="confirm" placeholder="Confirm your password" minlength="6" autocomplete="new-password" required>
      </div>

      <button type="submit" class="btn-main" style="margin-top:18px">Create Account</button>
      <p class="or">or</p>
  
      <p class="alt">Already have an account? <a href="login.php">Login</a></p>
    </form>
  </div>
</div>
</body>
</html>