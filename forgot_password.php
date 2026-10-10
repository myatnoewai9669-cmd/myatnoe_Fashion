<?php
require 'db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

/* Local testing: show the OTP on screen because XAMPP cannot send email.
   Set to false when you connect a real mail service. */
const SHOW_DEMO_OTP = true;
const OTP_MINUTES   = 10;
const OTP_MAX_TRIES = 5;

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function new_otp() {
  $_SESSION['fp_otp']      = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
  $_SESSION['fp_expires']  = time() + OTP_MINUTES * 60;
  $_SESSION['fp_tries']    = 0;
  $_SESSION['fp_verified'] = false;
}
function clear_fp() {
  foreach (['fp_email','fp_otp','fp_expires','fp_tries','fp_verified'] as $k) unset($_SESSION[$k]);
}

// Start over
if (isset($_GET['restart'])) { clear_fp(); header('Location: forgot_password.php'); exit; }
// Resend code
if (isset($_GET['resend']) && !empty($_SESSION['fp_email'])) { new_otp(); header('Location: forgot_password.php'); exit; }

$error = '';
$done  = false;
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // ---- Step 1: email ----
  if ($action === 'email') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = 'Please enter a valid email address.';
    } else {
      $st = $pdo->prepare("SELECT id FROM users WHERE email = ?");
      $st->execute([$email]);
      if (!$st->fetch()) {
        $error = 'Email not found.';
      } else {
        clear_fp();
        $_SESSION['fp_email'] = $email;
        new_otp();
      }
    }
  }

  // ---- Step 2: verify OTP ----
  elseif ($action === 'otp' && !empty($_SESSION['fp_email'])) {
    $entered = preg_replace('/\D/', '', ($_POST['otp1'] ?? '').($_POST['otp2'] ?? '').($_POST['otp3'] ?? '').($_POST['otp4'] ?? ''));
    if (time() > ($_SESSION['fp_expires'] ?? 0)) {
      $error = 'Code expired. Please resend a new code.';
    } elseif (($_SESSION['fp_tries'] ?? 0) >= OTP_MAX_TRIES) {
      $error = 'Too many wrong attempts. Please resend a new code.';
    } elseif (hash_equals($_SESSION['fp_otp'] ?? '', $entered)) {
      $_SESSION['fp_verified'] = true;
    } else {
      $_SESSION['fp_tries'] = ($_SESSION['fp_tries'] ?? 0) + 1;
      $error = 'Invalid OTP code.';
    }
  }

  // ---- Step 3: new password (only after OTP verified) ----
  elseif ($action === 'password' && !empty($_SESSION['fp_verified']) && !empty($_SESSION['fp_email'])) {
    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    if (strlen($pass) < 6) {
      $error = 'Password must be at least 6 characters.';
    } elseif ($pass !== $confirm) {
      $error = 'Passwords do not match.';
    } else {
      $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?")
          ->execute([password_hash($pass, PASSWORD_DEFAULT), $_SESSION['fp_email']]);
      clear_fp();
      $done = true;
    }
  }
}

// Decide which step to show
if ($done)                              $step = 4;
elseif (!empty($_SESSION['fp_verified'])) $step = 3;
elseif (!empty($_SESSION['fp_email']))    $step = 2;
else                                    $step = 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Forgot Password - Myat Noe Fashion</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --pink:#e8336d;--pink-dark:#c0204f;--pink-soft:#fad3df;--pink-mid:#f4b3c6;
  --text:#222;--muted:#6b7280;--border:#e5e7eb;--white:#fff;
}
body{
  font-family:Inter,Arial,sans-serif;
  background:linear-gradient(180deg,var(--pink-soft),var(--pink-mid));
  min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem;
}
.card{
  width:100%;max-width:380px;background:var(--white);border-radius:28px;
  box-shadow:0 20px 60px rgba(0,0,0,.15);overflow:hidden;
  animation:cardUp .6s cubic-bezier(.22,1,.36,1) both;
}
@keyframes cardUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
.head{background:var(--pink);padding:2rem 2rem 3rem;position:relative}
.head::after{content:'';position:absolute;bottom:-1px;left:0;right:0;height:34px;background:var(--white);border-radius:34px 34px 0 0}
.back{display:inline-block;color:rgba(255,255,255,.8);font-size:.8rem;margin-bottom:.8rem;text-decoration:none}
.back:hover{color:#fff}
.tag{color:rgba(255,255,255,.75);font-size:.72rem;font-weight:500;letter-spacing:.05em;margin-bottom:4px}
.title{color:#fff;font-size:1.7rem;font-weight:700}
.dots{display:flex;align-items:center;position:absolute;top:1.2rem;right:1.5rem}
.dot{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.35);transition:all .3s}
.dot+.dot{margin-left:5px}
.dot.active{background:#fff;width:20px;border-radius:4px}
.dot.done{background:#ffd6e3}
.body{padding:.5rem 2rem 2rem}
.icon-wrap{text-align:center;padding:1.2rem 0 .8rem}
.icon{
  width:85px;height:85px;border-radius:50%;display:flex;align-items:center;justify-content:center;
  font-size:2.2rem;margin:0 auto .8rem;background:linear-gradient(135deg,#fff0f5,var(--pink-soft));
  border:2px solid var(--pink-mid);animation:bounce 2s ease-in-out infinite;
}
@keyframes bounce{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
.sub{font-size:.8rem;color:var(--muted);line-height:1.5;margin-bottom:1.2rem;text-align:center}
.sub strong{color:var(--pink-dark)}
.err{
  background:#ffe3e8;color:var(--pink-dark);padding:.6rem .9rem;border-radius:10px;
  font-size:.8rem;margin-bottom:1rem;animation:shake .4s ease;
}
@keyframes shake{0%,100%{transform:translateX(0)}20%,60%{transform:translateX(-5px)}40%,80%{transform:translateX(5px)}}
.field{margin-bottom:1rem}
.field label{display:block;font-size:.7rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px}
.in{display:flex;align-items:center;gap:8px;border:1.5px solid var(--border);border-radius:12px;padding:0 .9rem;background:#fafafa;transition:all .2s}
.in:focus-within{border-color:var(--pink);box-shadow:0 0 0 3px rgba(232,51,109,.12);background:#fff}
.in span{font-size:.95rem;color:#aaa}
.in input{flex:1;border:0;outline:0;padding:.75rem 0;font-size:.9rem;font-family:inherit;background:transparent}
.otp{display:flex;justify-content:center;gap:12px;margin-bottom:1.2rem}
.otp input{
  width:56px;height:60px;border:2px solid var(--border);border-radius:14px;text-align:center;
  font-size:1.5rem;font-weight:700;font-family:inherit;color:var(--pink-dark);outline:none;background:#fafafa;transition:all .2s;
}
.otp input:focus{border-color:var(--pink);box-shadow:0 0 0 3px rgba(232,51,109,.12);background:#fff}
.demo{text-align:center;font-size:.72rem;color:var(--muted);background:#f9fafb;border:1px dashed var(--border);border-radius:8px;padding:6px 12px;margin-bottom:1rem}
.demo strong{color:var(--pink-dark);font-size:1rem;letter-spacing:.2em}
.resend{text-align:center;font-size:.75rem;color:var(--muted);margin-top:.9rem}
.resend a,.foot a{color:var(--pink);font-weight:600;text-decoration:none}
.bar{height:3px;background:var(--border);border-radius:3px;margin-top:6px;overflow:hidden}
.fill{height:100%;width:0;border-radius:3px;transition:width .4s,background .4s}
.hint{font-size:.65rem;color:var(--muted);margin-top:3px}
.btn{
  display:block;width:100%;padding:.85rem;background:var(--pink);color:#fff;border:0;border-radius:14px;
  font-size:.95rem;font-weight:700;font-family:inherit;cursor:pointer;text-align:center;text-decoration:none;
  transition:transform .2s,box-shadow .2s,filter .2s;
}
.btn:hover{transform:translateY(-2px);box-shadow:0 8px 22px rgba(232,51,109,.35);filter:brightness(1.05)}
.btn:active{transform:scale(.98)}
.foot{text-align:center;font-size:.78rem;color:var(--muted);margin-top:1.2rem}
.ok{text-align:center;padding:1.5rem 0}
.ok-circle{
  width:90px;height:90px;border-radius:50%;background:linear-gradient(135deg,var(--pink),#ff7aa5);color:#fff;
  display:flex;align-items:center;justify-content:center;font-size:2.5rem;margin:0 auto 1.2rem;
  box-shadow:0 0 40px rgba(232,51,109,.4);animation:pop .6s cubic-bezier(.22,1,.36,1) both;
}
@keyframes pop{from{transform:scale(0) rotate(-45deg);opacity:0}70%{transform:scale(1.1)}to{transform:scale(1);opacity:1}}
.ok h3{font-size:1.3rem;color:var(--pink-dark);margin-bottom:6px}
.ok p{font-size:.82rem;color:var(--muted);margin-bottom:1.5rem;line-height:1.5}
</style>
</head>
<body>
<div class="card">

<?php
function dots($n) {
  echo '<div class="dots">';
  for ($i = 1; $i <= 4; $i++) {
    $c = $i < $n ? 'done' : ($i === $n ? 'active' : '');
    echo "<div class=\"dot $c\"></div>";
  }
  echo '</div>';
}
?>

<?php if ($step === 1): ?>
  <div class="head">
    <?php dots(1); ?>
    <a href="login.php" class="back">&larr; Back</a>
    <div class="tag">Forgot Password</div>
    <div class="title">Reset Password</div>
  </div>
  <div class="body">
    <div class="icon-wrap"><div class="icon">&#128272;</div></div>
    <div class="sub">Enter your email address to<br>receive a verification code</div>
    <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="action" value="email">
      <div class="field">
        <label for="email">Email Address</label>
        <div class="in"><span>&#9993;</span>
          <input id="email" type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="Enter your email" autocomplete="email" required>
        </div>
      </div>
      <button class="btn" type="submit">Send Code &rarr;</button>
    </form>
    <div class="foot">Remember your password? <a href="login.php">Sign In</a></div>
  </div>

<?php elseif ($step === 2): ?>
  <div class="head">
    <?php dots(2); ?>
    <a href="forgot_password.php?restart=1" class="back">&larr; Change email</a>
    <div class="tag">Verification</div>
    <div class="title">Verify Your Email</div>
  </div>
  <div class="body">
    <div class="icon-wrap"><div class="icon">&#128231;</div></div>
    <div class="sub">Enter the 4-digit code sent to<br><strong><?= e($_SESSION['fp_email'] ?? '') ?></strong></div>
    <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
    <?php if (SHOW_DEMO_OTP && isset($_SESSION['fp_otp'])): ?>
      <div class="demo">Demo OTP: <strong><?= e($_SESSION['fp_otp']) ?></strong></div>
    <?php endif; ?>
    <form method="post">
      <input type="hidden" name="action" value="otp">
      <div class="otp">
        <input type="text" name="otp1" maxlength="1" inputmode="numeric" autocomplete="one-time-code" required autofocus>
        <input type="text" name="otp2" maxlength="1" inputmode="numeric" required>
        <input type="text" name="otp3" maxlength="1" inputmode="numeric" required>
        <input type="text" name="otp4" maxlength="1" inputmode="numeric" required>
      </div>
      <button class="btn" type="submit">Verify &rarr;</button>
    </form>
    <div class="resend">Didn't receive the code? <a href="forgot_password.php?resend=1">Resend</a></div>
  </div>

<?php elseif ($step === 3): ?>
  <div class="head">
    <?php dots(3); ?>
    <div class="tag">Almost Done</div>
    <div class="title">New Password</div>
  </div>
  <div class="body">
    <div class="icon-wrap"><div class="icon">&#128273;</div></div>
    <div class="sub">Your new password must be at least<br>6 characters long.</div>
    <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="action" value="password">
      <div class="field">
        <label for="pwd">New Password</label>
        <div class="in"><span>&#128274;</span>
          <input id="pwd" type="password" name="password" placeholder="Enter new password" minlength="6" autocomplete="new-password" required>
        </div>
        <div class="bar"><div class="fill" id="fill"></div></div>
        <div class="hint" id="hint">Enter a password</div>
      </div>
      <div class="field">
        <label for="cf">Confirm Password</label>
        <div class="in"><span>&#128274;</span>
          <input id="cf" type="password" name="confirm" placeholder="Confirm new password" minlength="6" autocomplete="new-password" required>
        </div>
      </div>
      <button class="btn" type="submit">Save &rarr;</button>
    </form>
  </div>

<?php else: ?>
  <div class="head" style="padding-bottom:2.5rem">
    <?php dots(4); ?>
    <div class="tag">All Done!</div>
    <div class="title">Password Changed</div>
  </div>
  <div class="body">
    <div class="ok">
      <div class="ok-circle">&#10003;</div>
      <h3>Password Changed!</h3>
      <p>Your password has been changed successfully.<br>You can now sign in.</p>
      <a href="login.php" class="btn">Back to Sign In &rarr;</a>
    </div>
  </div>
<?php endif; ?>

</div>

<script>
// OTP boxes: auto-advance, backspace, paste
var boxes = document.querySelectorAll('.otp input');
boxes.forEach(function(inp, i){
  inp.addEventListener('input', function(){
    this.value = this.value.replace(/\D/g, '');
    if (this.value && i < boxes.length - 1) boxes[i+1].focus();
  });
  inp.addEventListener('keydown', function(e){
    if (e.key === 'Backspace' && !this.value && i > 0) boxes[i-1].focus();
  });
  inp.addEventListener('paste', function(e){
    var t = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 4);
    if (!t) return;
    e.preventDefault();
    t.split('').forEach(function(ch, k){ if (boxes[k]) boxes[k].value = ch; });
    boxes[Math.min(t.length, 3)].focus();
  });
});

// Password strength
var pwd = document.getElementById('pwd');
if (pwd) {
  pwd.addEventListener('input', function(){
    var v = this.value, s = 0;
    if (v.length >= 6) s++;
    if (v.length >= 10) s++;
    if (/[A-Z]/.test(v)) s++;
    if (/[0-9]/.test(v)) s++;
    if (/[^A-Za-z0-9]/.test(v)) s++;
    var lv = [
      {w:'0%',  c:'transparent', t:'Enter a password'},
      {w:'25%', c:'#ef4444',     t:'Weak'},
      {w:'50%', c:'#f97316',     t:'Fair'},
      {w:'75%', c:'#eab308',     t:'Good'},
      {w:'100%',c:'#22c55e',     t:'Strong'}
    ][Math.min(s, 4)];
    var f = document.getElementById('fill'), h = document.getElementById('hint');
    f.style.width = lv.w; f.style.background = lv.c;
    h.textContent = lv.t; h.style.color = lv.c === 'transparent' ? '' : lv.c;
  });
}
</script>
</body>
</html>