<?php
require 'db.php';
if (empty($_SESSION['user_id'])) { header('Location: login.php?next=profile.php'); exit; }
$uid = (int)$_SESSION['user_id'];
$tab = ($_GET['tab'] ?? '') === 'password' ? 'password' : 'profile';
$err = '';
$editInfo = isset($_GET['edit']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $a = $_POST['action'] ?? '';

  if ($a === 'info') {
    $name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if ($name === '') { $err = 'Full name is required.'; $editInfo = true; }
    else {
      $pdo->prepare("UPDATE users SET full_name=?, phone=? WHERE id=?")->execute([$name, $phone, $uid]);
      $_SESSION['user_name'] = $name;
      header('Location: profile.php?msg=' . urlencode('Profile updated.')); exit;
    }
  }

  if ($a === 'address') {
    $id    = (int)($_POST['id'] ?? 0);
    $label = trim($_POST['label'] ?? '') ?: 'Home';
    $line  = trim($_POST['address_line'] ?? '');
    $city  = trim($_POST['city'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $def   = isset($_POST['is_default']) ? 1 : 0;
    if ($line === '') { $err = 'Street address is required.'; $_GET['addr'] = $id ?: 'new'; }
    else {
      if ($def) $pdo->prepare("UPDATE addresses SET is_default=0 WHERE user_id=?")->execute([$uid]);
      if ($id) $pdo->prepare("UPDATE addresses SET label=?,address_line=?,city=?,phone=?,is_default=? WHERE id=? AND user_id=?")
                   ->execute([$label, $line, $city, $phone, $def, $id, $uid]);
      else     $pdo->prepare("INSERT INTO addresses (user_id,label,address_line,city,phone,is_default) VALUES (?,?,?,?,?,?)")
                   ->execute([$uid, $label, $line, $city, $phone, $def]);
      header('Location: profile.php?msg=' . urlencode('Address saved.') . '#addresses'); exit;
    }
  }

  if ($a === 'del_address') {
    $pdo->prepare("DELETE FROM addresses WHERE id=? AND user_id=?")->execute([(int)$_POST['id'], $uid]);
    header('Location: profile.php?msg=' . urlencode('Address deleted.') . '#addresses'); exit;
  }

  if ($a === 'password') {
    $tab = 'password';
    $s = $pdo->prepare("SELECT password_hash FROM users WHERE id=?"); $s->execute([$uid]);
    $hash = $s->fetchColumn();
    if (!password_verify($_POST['old'] ?? '', $hash)) $err = 'Old password is wrong.';
    elseif (strlen($_POST['new'] ?? '') < 6) $err = 'New password must be at least 6 characters.';
    elseif (($_POST['new'] ?? '') !== ($_POST['confirm'] ?? '')) $err = 'New passwords do not match.';
    else {
      $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([password_hash($_POST['new'], PASSWORD_DEFAULT), $uid]);
      header('Location: profile.php?tab=password&msg=' . urlencode('Password changed.')); exit;
    }
  }
}

$user = $pdo->prepare("SELECT * FROM users WHERE id=?"); $user->execute([$uid]); $user = $user->fetch();
$addrs = $pdo->prepare("SELECT * FROM addresses WHERE user_id=? ORDER BY is_default DESC, id DESC");
$addrs->execute([$uid]); $addrs = $addrs->fetchAll();

$mode = $_GET['addr'] ?? null;
$form = ['id'=>0,'label'=>'Home','address_line'=>'','city'=>'','phone'=>'','is_default'=>0];
if ($mode && $mode !== 'new') {
  foreach ($addrs as $x) if ($x['id'] == $mode) $form = $x;
}
include 'header.php';
?>
<div class="ct">
  <div class="bc"><a href="index.php">Home</a> › My Account</div>
  <div class="pf-wrap">
    <?php include 'account_nav.php'; ?>
    <div>
      <?php if (!empty($_GET['msg'])): ?><p class="pf-ok"><?= htmlspecialchars($_GET['msg']) ?></p><?php endif; ?>
      <?php if ($err): ?><p class="pf-er"><?= htmlspecialchars($err) ?></p><?php endif; ?>

      <?php if ($tab === 'password'): ?>
        <div class="pf-card">
          <div class="pf-head"><h3>Change Password</h3></div>
          <form method="post" style="max-width:420px">
            <input type="hidden" name="action" value="password">
            <input class="pf-in" type="password" name="old" placeholder="Old password" required>
            <input class="pf-in" type="password" name="new" placeholder="New password (min 6 characters)" required>
            <input class="pf-in" type="password" name="confirm" placeholder="Confirm new password" required>
            <button class="btn">Update Password</button>
          </form>
        </div>

      <?php else: ?>
        <div class="pf-card">
          <div class="pf-head">
            <h3>Profile Information</h3>
            <?php if (!$editInfo): ?><a href="profile.php?edit=1">✎ Edit</a><?php endif; ?>
          </div>
          <?php if ($editInfo): ?>
            <form method="post" style="max-width:420px">
              <input type="hidden" name="action" value="info">
              <div class="pf-f"><small>Full Name</small><input class="pf-in" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required></div>
              <div class="pf-f"><small>Email</small><input class="pf-in" value="<?= htmlspecialchars($user['email']) ?>" disabled></div>
              <div class="pf-f"><small>Phone</small><input class="pf-in" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"></div>
              <div class="pf-btns"><button class="btn">Save</button><a class="btn out" href="profile.php">Cancel</a></div>
            </form>
          <?php else: ?>
            <div class="pf-f"><small>Full Name</small><span><?= htmlspecialchars($user['full_name']) ?></span></div>
            <div class="pf-f"><small>Email</small><span><?= htmlspecialchars($user['email']) ?></span></div>
            <div class="pf-f"><small>Phone</small><span><?= htmlspecialchars($user['phone'] ?: '-') ?></span></div>
          <?php endif; ?>
        </div>

        <div class="pf-card" id="addresses">
          <div class="pf-head">
            <h3>Saved Addresses</h3>
            <?php if (!$mode): ?><a href="profile.php?addr=new#addresses">+ Add New</a><?php endif; ?>
          </div>

          <?php if ($mode): ?>
            <form method="post" style="max-width:480px;margin-bottom:18px">
              <input type="hidden" name="action" value="address">
              <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">
              <input class="pf-in" name="label" placeholder="Label (Home / Office)" value="<?= htmlspecialchars($form['label']) ?>">
              <input class="pf-in" name="address_line" placeholder="Street address" value="<?= htmlspecialchars($form['address_line']) ?>" required>
              <input class="pf-in" name="city" placeholder="City" value="<?= htmlspecialchars($form['city'] ?? '') ?>">
              <input class="pf-in" name="phone" placeholder="Phone" value="<?= htmlspecialchars($form['phone'] ?? '') ?>">
              <label style="display:block;font-size:13px;margin-bottom:12px"><input type="checkbox" name="is_default" <?= $form['is_default'] ? 'checked' : '' ?>> Set as default address</label>
              <div class="pf-btns"><button class="btn">Save Address</button><a class="btn out" href="profile.php#addresses">Cancel</a></div>
            </form>
          <?php endif; ?>

          <?php foreach ($addrs as $x): ?>
            <div class="pf-ad">
              <div>
                <b><?= htmlspecialchars($x['label']) ?></b><?= $x['is_default'] ? '<span class="pf-tag">Default</span>' : '' ?><br>
                <?= htmlspecialchars($x['address_line']) ?><br>
                <?= htmlspecialchars($x['city'] ?? '') ?><br>
                <small style="color:var(--grey)"><?= htmlspecialchars($x['phone'] ?? '') ?></small>
              </div>
              <div class="pf-ic">
                <a href="profile.php?addr=<?= $x['id'] ?>#addresses" title="Edit">✎</a>
                <form method="post" onsubmit="return confirm('Delete this address?')">
                  <input type="hidden" name="action" value="del_address">
                  <input type="hidden" name="id" value="<?= $x['id'] ?>">
                  <button title="Delete">🗑</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (!$addrs && !$mode): ?><p style="color:var(--grey);font-size:14px">No saved addresses yet.</p><?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include 'footer.php'; ?>