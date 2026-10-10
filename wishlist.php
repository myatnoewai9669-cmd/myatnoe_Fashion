<?php
require 'db.php';
$ajax = isset($_POST['ajax']);

if (empty($_SESSION['user_id'])) {
  if ($ajax) { header('Content-Type: application/json'); echo json_encode(['login' => true]); exit; }
  header('Location: login.php?next=wishlist.php'); exit;
}
$uid = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pid = (int)($_POST['id'] ?? 0);
  $ok = $pdo->prepare("SELECT id FROM products WHERE id=?");
  $ok->execute([$pid]);
  $on = false;
  if ($ok->fetch()) {
    $ex = $pdo->prepare("SELECT id FROM wishlist WHERE user_id=? AND product_id=?");
    $ex->execute([$uid, $pid]);
    if ($ex->fetch()) {
      $pdo->prepare("DELETE FROM wishlist WHERE user_id=? AND product_id=?")->execute([$uid, $pid]);
    } else {
      $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?,?)")->execute([$uid, $pid]);
      $on = true;
    }
  }
  if ($ajax) {
    $c = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id=?");
    $c->execute([$uid]);
    header('Content-Type: application/json');
    echo json_encode(['on' => $on, 'count' => (int)$c->fetchColumn()]);
    exit;
  }
  header('Location: ' . (isset($_POST['back']) ? 'wishlist.php' : 'product.php?id=' . $pid)); exit;
}

$st = $pdo->prepare("SELECT p.* FROM wishlist w JOIN products p ON p.id=w.product_id WHERE w.user_id=? ORDER BY w.id DESC");
$st->execute([$uid]);
$items = $st->fetchAll();
include 'header.php';
?>
<div class="ct">
  <div class="bc"><a href="index.php">Home</a> › Wishlist</div>
  <div class="pf-wrap">
    <?php include 'account_nav.php'; ?>
    <div>
      <div class="pf-card">
        <div class="pf-head"><h3>My Wishlist (<?= count($items) ?>)</h3></div>
        <?php if (!$items): ?>
          <p style="color:var(--grey);font-size:14px">Your wishlist is empty. <a href="shop.php" style="color:var(--pink);font-weight:600">Browse products →</a></p>
        <?php else: ?>
          <div class="pg wl-g">
            <?php foreach ($items as $p): ?>
              <div>
                <?= card($p) ?>
                <form method="post" class="wl-f">
                  <input type="hidden" name="id" value="<?= $p['id'] ?>">
                  <input type="hidden" name="back" value="1">
                  <button class="btn out">Remove</button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php include 'footer.php'; ?>