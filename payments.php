<?php
require 'db.php';
if (($_SESSION['role'] ?? '') !== 'admin') { header('Location: ../login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['pstatus'] ?? '', ['pending','paid','failed','refunded'])) {
  $pdo->prepare("UPDATE orders SET payment_status=? WHERE id=?")->execute([$_POST['pstatus'], (int)$_POST['id']]);
  header('Location: payments.php?' . http_build_query(['method' => $_GET['method'] ?? '', 'status' => $_GET['status'] ?? ''])); exit;
}

$method = $_GET['method'] ?? '';
$status = $_GET['status'] ?? '';
$sql = "SELECT o.*, u.full_name FROM orders o JOIN users u ON u.id=o.user_id WHERE 1";
$args = [];
if (in_array($method, ['bank_transfer','cod','ewallet'])) { $sql .= " AND o.payment_method=?"; $args[] = $method; }
if (in_array($status, ['pending','paid','failed','refunded'])) { $sql .= " AND o.payment_status=?"; $args[] = $status; }
$sql .= " ORDER BY o.id DESC";
$st = $pdo->prepare($sql); $st->execute($args);
$rows = $st->fetchAll();

function sum($pdo, $s) { return $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status='$s'")->fetchColumn(); }
$received = sum($pdo, 'paid');
$pending  = $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status='pending' AND status<>'cancelled'")->fetchColumn();
$refunded = sum($pdo, 'refunded');
$count    = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

$mLabel = ['bank_transfer' => 'Bank Transfer', 'cod' => 'COD', 'ewallet' => 'E-Wallet'];
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Payments  Admin</title>
<link rel="stylesheet" href="admin.css">
</head><body>
<div class="adm">
  <?php include 'side.php'; ?>
  <main class="main">
    <div class="top">
      <h2>Payments</h2>
      <form method="get" class="srch">
        <select name="method">
          <option value="">All methods</option>
          <?php foreach ($mLabel as $k => $v): ?><option value="<?= $k ?>" <?= $method==$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?>
        </select>
        <select name="status">
          <option value="">All status</option>
          <?php foreach (['pending','paid','failed','refunded'] as $s): ?><option value="<?= $s ?>" <?= $status==$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
        </select>
        <button class="sv">Filter</button>
      </form>
    </div>

    <div class="cards">
      <div class="card2"><small>Total Received</small><b><?= money($received) ?></b></div>
      <div class="card2"><small>Pending Payment</small><b><?= money($pending) ?></b></div>
      <div class="card2"><small>Refunded</small><b><?= money($refunded) ?></b></div>
      <div class="card2"><small>Total Transactions</small><b><?= $count ?></b></div>
    </div>

    <div class="box">
      <table class="tb">
        <tr><th>Order</th><th>Customer</th><th>Method</th><th>Amount</th><th>Date</th><th>Payment</th><th></th></tr>
        <?php foreach ($rows as $o): ?>
        <tr>
          <form method="post">
            <td>#<?= htmlspecialchars($o['order_no']) ?></td>
            <td><?= htmlspecialchars($o['full_name']) ?></td>
            <td><?= $mLabel[$o['payment_method']] ?? $o['payment_method'] ?></td>
            <td><b><?= money($o['total']) ?></b></td>
            <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
            <td>
              <input type="hidden" name="id" value="<?= $o['id'] ?>">
              <select name="pstatus">
                <?php foreach (['pending','paid','failed','refunded'] as $s): ?>
                  <option value="<?= $s ?>" <?= $o['payment_status']==$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td><button class="sv">Save</button></td>
          </form>
        </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="7" style="color:#999;text-align:center;padding:26px">No payments found.</td></tr><?php endif; ?>
      </table>
    </div>
  </main>
</div>
</body></html>