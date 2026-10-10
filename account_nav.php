<?php
$cur = basename($_SERVER['PHP_SELF']);
$onPw = ($_GET['tab'] ?? '') === 'password';
$me = $pdo->prepare("SELECT full_name, email FROM users WHERE id=?");
$me->execute([$_SESSION['user_id']]);
$me = $me->fetch();
?>
<aside class="pf-side">
  <div class="pf-me">
    <div class="pf-av"><?= htmlspecialchars(mb_strtoupper(mb_substr($me['full_name'], 0, 1))) ?></div>
    <div><b><?= htmlspecialchars($me['full_name']) ?></b><small><?= htmlspecialchars($me['email']) ?></small></div>
  </div>
  <a class="<?= $cur=='profile.php' && !$onPw ? 'on' : '' ?>" href="profile.php">👤 Profile</a>
  <a href="profile.php#addresses">📍 Saved Addresses</a>
  <a href="wishlist.php">♡ Wishlist</a>
  <a class="<?= $cur=='orders.php' ? 'on' : '' ?>" href="orders.php">🧾 Order History</a>
  <a class="<?= $cur=='track.php' ? 'on' : '' ?>" href="track.php">🚚 Track Order</a>
  <a class="<?= $cur=='profile.php' && $onPw ? 'on' : '' ?>" href="profile.php?tab=password">🔒 Change Password</a>
  <?php if (($_SESSION['role'] ?? '') === 'admin'): ?><a href="admin.index.php">⚙ Admin Panel</a><?php endif; ?>
  <a href="logout.php">⎋ Logout</a>
</aside>