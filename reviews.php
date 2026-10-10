<?php
require 'db.php';
if (($_SESSION['role'] ?? '') !== 'admin') { header('Location:login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
  $id = (int)$_POST['delete'];
  $s = $pdo->prepare("SELECT product_id FROM reviews WHERE id=?"); $s->execute([$id]);
  $pid = $s->fetchColumn();
  $pdo->prepare("DELETE FROM reviews WHERE id=?")->execute([$id]);
  if ($pid) recalc_rating($pdo, $pid);
  header('Location: reviews.php?msg=Review+deleted'); exit;
}

$rate = (int)($_GET['rating'] ?? 0);
$sql = "SELECT r.*, p.name AS pname, u.full_name FROM reviews r
        JOIN products p ON p.id=r.product_id JOIN users u ON u.id=r.user_id";
$args = [];
if ($rate >= 1 && $rate <= 5) { $sql .= " WHERE r.rating=?"; $args[] = $rate; }
$sql .= " ORDER BY r.id DESC";
$st = $pdo->prepare($sql); $st->execute($args);
$rows = $st->fetchAll();

$total = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
$avg   = $pdo->query("SELECT COALESCE(ROUND(AVG(rating),1),0) FROM reviews")->fetchColumn();
$five  = $pdo->query("SELECT COUNT(*) FROM reviews WHERE rating=5")->fetchColumn();
$low   = $pdo->query("SELECT COUNT(*) FROM reviews WHERE rating<=2")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reviews – Admin</title>
<link rel="stylesheet" href="admin.css">
</head><body>
<div class="adm">
  <?php include 'side.php'; ?>
  <main class="main">
    <div class="top">
      <h2>Reviews</h2>
      <form method="get" class="srch">
        <select name="rating">
          <option value="0">All ratings</option>
          <?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>" <?= $rate==$i?'selected':'' ?>><?= $i ?> stars</option><?php endfor; ?>
        </select>
        <button class="sv">Filter</button>
      </form>
    </div>
    <?php if (isset($_GET['msg'])): ?><p class="okmsg"><?= htmlspecialchars($_GET['msg']) ?></p><?php endif; ?>

    <div class="cards">
      <div class="card2"><small>Total Reviews</small><b><?= $total ?></b></div>
      <div class="card2"><small>Average Rating</small><b>★ <?= $avg ?> / 5</b></div>
      <div class="card2"><small>5-Star Reviews</small><b><?= $five ?></b></div>
      <div class="card2"><small>Low (1–2 Stars)</small><b><?= $low ?></b></div>
    </div>

    <div class="box">
      <table class="tb">
        <tr><th>Product</th><th>Customer</th><th>Rating</th><th>Comment</th><th>Date</th><th></th></tr>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><b><?= htmlspecialchars($r['pname']) ?></b></td>
          <td><?= htmlspecialchars($r['full_name']) ?></td>
          <td style="color:#f5a623;white-space:nowrap"><?= str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']) ?></td>
          <td style="max-width:320px"><?= htmlspecialchars($r['comment'] ?: '-') ?></td>
          <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
          <td>
            <form method="post" onsubmit="return confirm('Delete this review?')">
              <button class="dl" name="delete" value="<?= $r['id'] ?>">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="6" style="color:#999;text-align:center;padding:26px">No reviews yet.</td></tr><?php endif; ?>
      </table>
    </div>
  </main>
</div>
</body></html>