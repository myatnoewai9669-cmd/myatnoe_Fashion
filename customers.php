<?php
require 'db.php';
if (($_SESSION['role'] ?? '') !== 'admin') { header('Location:login.php'); exit; }

$q = trim($_GET['q'] ?? '');
$sql = "SELECT u.*, COUNT(o.id) AS orders,
        COALESCE(SUM(CASE WHEN o.status<>'cancelled' THEN o.total END),0) AS spent
        FROM users u LEFT JOIN orders o ON o.user_id=u.id
        WHERE u.role='customer'";
$args = [];
if ($q !== '') {
  $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
  $args = ["%$q%", "%$q%", "%$q%"];
}
$sql .= " GROUP BY u.id ORDER BY u.id DESC";
$st = $pdo->prepare($sql); $st->execute($args);
$rows = $st->fetchAll();

$total   = $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$newM    = $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer' AND DATE_FORMAT(created_at,'%Y-%m')='" . date('Y-m') . "'")->fetchColumn();
$buyers  = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM orders")->fetchColumn();
$revenue = $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status<>'cancelled'")->fetchColumn();

$view = null; $vorders = [];
if (isset($_GET['view'])) {
  $s = $pdo->prepare("SELECT * FROM users WHERE id=? AND role='customer'"); $s->execute([(int)$_GET['view']]);
  $view = $s->fetch();
  if ($view) {
    $s = $pdo->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY id DESC LIMIT 10");
    $s->execute([$view['id']]); $vorders = $s->fetchAll();
  }
}
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Customers – Admin</title>
<link rel="stylesheet" href="admin.css">
</head><body>
<div class="adm">
  <?php include 'side.php'; ?>
  <main class="main">
    <div class="top">
      <h2>Customers</h2>
      <form method="get" class="srch">
        <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search name, email or phone">
        <button class="sv">Search</button>
      </form>
    </div>

    <div class="cards">
      <div class="card2"><small>Total Customers</small><b><?= $total ?></b></div>
      <div class="card2"><small>New This Month</small><b><?= $newM ?></b></div>
      <div class="card2"><small>Customers Who Ordered</small><b><?= $buyers ?></b></div>
      <div class="card2"><small>Total Revenue</small><b><?= money($revenue) ?></b></div>
    </div>

    <?php if ($view): ?>
    <div class="box" style="margin-bottom:18px">
      <h3><?= htmlspecialchars($view['full_name']) ?> <a href="customers.php">✕ Close</a></h3>
      <p style="color:#777;margin-bottom:12px"><?= htmlspecialchars($view['email']) ?> · <?= htmlspecialchars($view['phone'] ?: 'No phone') ?> · Joined <?= date('d M Y', strtotime($view['created_at'])) ?></p>
      <?php if (!$vorders): ?><p style="color:#999">No orders yet.</p><?php endif; ?>
      <?php foreach ($vorders as $o): ?>
        <div class="rec" style="grid-template-columns:110px 1fr auto">
          <span>#<?= htmlspecialchars($o['order_no']) ?></span>
          <span class="st <?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span>
          <b><?= money($o['total']) ?></b>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="box">
      <table class="tb">
        <tr><th>Customer</th><th>Phone</th><th>Orders</th><th>Total Spent</th><th>Joined</th><th></th></tr>
        <?php foreach ($rows as $c): ?>
        <tr>
          <td>
            <div class="who">
              <div class="av2"><?= htmlspecialchars(mb_strtoupper(mb_substr($c['full_name'], 0, 1))) ?></div>
              <div><b><?= htmlspecialchars($c['full_name']) ?></b><br><small style="color:#888"><?= htmlspecialchars($c['email']) ?></small></div>
            </div>
          </td>
          <td><?= htmlspecialchars($c['phone'] ?: '-') ?></td>
          <td><?= $c['orders'] ?></td>
          <td><?= money($c['spent']) ?></td>
          <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
          <td><a class="ed" href="customers.php?view=<?= $c['id'] ?>">View</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="6" style="color:#999;text-align:center;padding:26px">No customers found.</td></tr><?php endif; ?>
      </table>
    </div>
  </main>
</div>
</body></html>