<?php
require 'db.php';
$_SESSION['cart'] ??= [];
$a = $_POST['action'] ?? '';

if ($a === 'add') {
  $id    = (int)($_POST['id'] ?? 0);
  $color = trim($_POST['color'] ?? '');
  $size  = trim($_POST['size'] ?? '');
  $qty   = max(1, (int)($_POST['qty'] ?? 1));
  $ok = $pdo->prepare("SELECT id FROM products WHERE id=?");
  $ok->execute([$id]);
  if ($ok->fetch()) {
    $key = $id . '|' . $color . '|' . $size;
    $_SESSION['cart'][$key] = ($_SESSION['cart'][$key] ?? 0) + $qty;
  }
  header('Location: ' . (isset($_POST['buy']) ? 'checkout.php' : 'cart.php')); exit;
}

if ($a === 'update') {
  foreach ($_POST['qty'] ?? [] as $k => $q) {
    if (!isset($_SESSION['cart'][$k])) continue;
    if ((int)$q < 1) unset($_SESSION['cart'][$k]); else $_SESSION['cart'][$k] = (int)$q;
  }
  if (isset($_POST['remove'])) unset($_SESSION['cart'][$_POST['remove']]);
  header('Location: cart.php'); exit;
}

if ($a === 'coupon') {
  $st = $pdo->prepare("SELECT * FROM coupons WHERE code=? AND is_active=1 AND (expires_at IS NULL OR expires_at>=CURDATE())");
  $st->execute([trim($_POST['code'] ?? '')]);
  $c = $st->fetch();
  $_SESSION['coupon'] = $c ?: null;
  header('Location: cart.php?c=' . ($c ? 'ok' : 'bad')); exit;
}

$rows = []; $subtotal = 0;
foreach ($_SESSION['cart'] as $key => $qty) {
  [$id, $color, $size] = array_pad(explode('|', $key), 3, '');
  $st = $pdo->prepare("SELECT * FROM products WHERE id=?");
  $st->execute([(int)$id]);
  if ($p = $st->fetch()) {
    $line = $p['price'] * $qty;
    $subtotal += $line;
    $rows[] = compact('key', 'p', 'color', 'size', 'qty', 'line');
  }
}
$cp   = $_SESSION['coupon'] ?? null;
$disc = $cp ? round($subtotal * $cp['discount_percent'] / 100) : 0;
$ship = $rows ? 30000 : 0;
$total = $subtotal - $disc + $ship;
include 'header.php';
?>
<div class="ct">
  <div class="ca-top">
    <h2>Shopping Cart</h2>
    <a href="shop.php">Continue Shopping ←</a>
  </div>

  <?php if (!$rows): ?>
    <div class="ca-empty">
      <p>Your cart is empty.</p>
      <a class="btn" href="shop.php">Start Shopping</a>
    </div>
  <?php else: ?>
  <form method="post" id="cf">
    <input type="hidden" name="action" value="update">
    <table class="ca-tb">
      <tr><th>Product</th><th>Price</th><th>Quantity</th><th>Total</th><th></th></tr>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td>
          <div class="ca-p">
            <div class="ca-img">
              <?php if ($r['p']['main_image']): ?><img src="<?= htmlspecialchars($r['p']['main_image']) ?>" alt="">
              <?php else: ?><span><?= htmlspecialchars(mb_substr($r['p']['name'], 0, 1)) ?></span><?php endif; ?>
            </div>
            <div>
              <b><?= htmlspecialchars($r['p']['name']) ?></b>
              <small>Color: <?= htmlspecialchars($r['color'] ?: '-') ?> / Size: <?= htmlspecialchars($r['size'] ?: '-') ?></small>
            </div>
          </div>
        </td>
        <td><?= money($r['p']['price']) ?></td>
        <td>
          <div class="qb">
            <button type="button" onclick="step(this,-1)">−</button>
            <input name="qty[<?= htmlspecialchars($r['key']) ?>]" value="<?= $r['qty'] ?>" readonly>
            <button type="button" onclick="step(this,1)">+</button>
          </div>
        </td>
        <td><b><?= money($r['line']) ?></b></td>
        <td><button class="ca-x" name="remove" value="<?= htmlspecialchars($r['key']) ?>" title="Remove">✕</button></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </form>

  <div class="ca-bottom">
    <form method="post" class="ca-cp">
      <input type="hidden" name="action" value="coupon">
      <label>Have a coupon code?</label>
      <div>
        <input name="code" placeholder="Enter coupon code" value="<?= htmlspecialchars($cp['code'] ?? '') ?>">
        <button class="btn">Apply</button>
      </div>
      <?php if (($_GET['c'] ?? '') === 'bad'): ?><small style="color:#c0204f">Invalid or expired coupon.</small><?php endif; ?>
      <?php if ($cp): ?><small style="color:#13803f">Coupon applied: <?= (int)$cp['discount_percent'] ?>% off</small><?php endif; ?>
    </form>

    <div class="ca-sum">
      <p><span>Subtotal</span><span><?= money($subtotal) ?></span></p>
      <?php if ($disc): ?><p><span>Discount</span><span>- <?= money($disc) ?></span></p><?php endif; ?>
      <p><span>Shipping</span><span><?= money($ship) ?></span></p>
      <p class="tot"><span>Total</span><span><?= money($total) ?></span></p>
      <a class="btn" href="checkout.php">Proceed to Checkout →</a>
    </div>
  </div>
  <?php endif; ?>
</div>
<script>
function step(btn, d){
  const i = btn.parentNode.querySelector('input');
  i.value = Math.max(1, (+i.value) + d);
  document.getElementById('cf').submit();
}
</script>
<?php include 'footer.php'; ?>