<?php
require 'db.php';
if (empty($_SESSION['user_id'])) { header('Location: login.php?next=checkout.php'); exit; }
if (empty($_SESSION['cart'])) { header('Location: cart.php'); exit; }
$uid = (int)$_SESSION['user_id'];

$rows = []; $subtotal = 0;
foreach ($_SESSION['cart'] as $key => $qty) {
  [$id, $color, $size] = array_pad(explode('|', $key), 3, '');
  $st = $pdo->prepare("SELECT * FROM products WHERE id=?"); $st->execute([(int)$id]);
  if ($p = $st->fetch()) {
    $subtotal += $p['price'] * $qty;
    $rows[] = compact('p', 'color', 'size', 'qty');
  }
}
if (!$rows) { header('Location: cart.php'); exit; }

$cp   = $_SESSION['coupon'] ?? null;
$disc = $cp ? round($subtotal * $cp['discount_percent'] / 100) : 0;
$fees = ['regular' => 30000, 'express' => 50000];

$u = $pdo->prepare("SELECT * FROM users WHERE id=?"); $u->execute([$uid]); $user = $u->fetch();
$a = $pdo->prepare("SELECT * FROM addresses WHERE user_id=? ORDER BY is_default DESC, id DESC");
$a->execute([$uid]); $addrs = $a->fetchAll();

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $delivery = ($_POST['delivery'] ?? '') === 'express' ? 'express' : 'regular';
  $pay = in_array($_POST['payment'] ?? '', ['bank_transfer', 'cod', 'ewallet']) ? $_POST['payment'] : 'bank_transfer';
  $ship = $fees[$delivery];
  $total = $subtotal - $disc + $ship;
  $sel = $_POST['address_id'] ?? 'new';
  $addrId = 0;

  if ($sel !== 'new' && $addrs) {
    foreach ($addrs as $x) if ($x['id'] == $sel) $addrId = (int)$x['id'];
    if (!$addrId) $err = 'Please choose a valid address.';
  } else {
    $line = trim($_POST['address_line'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if ($line === '' || $city === '' || $phone === '') $err = 'Please fill in address, city and phone.';
  }

  if (!$err) {
    try {
      $pdo->beginTransaction();
      if (!$addrId) {
        $pdo->prepare("INSERT INTO addresses (user_id,label,address_line,city,phone,is_default) VALUES (?,?,?,?,?,?)")
            ->execute([$uid, 'Home', $line, $city, $phone, $addrs ? 0 : 1]);
        $addrId = (int)$pdo->lastInsertId();
      }
      $orderNo = 'MN' . date('ymd') . rand(1000, 9999);
      $pdo->prepare("INSERT INTO orders (order_no,user_id,address_id,delivery_method,payment_method,coupon_id,subtotal,shipping_fee,total) VALUES (?,?,?,?,?,?,?,?,?)")
          ->execute([$orderNo, $uid, $addrId, $delivery, $pay, $cp['id'] ?? null, $subtotal, $ship, $total]);
      $oid = (int)$pdo->lastInsertId();
      $ins = $pdo->prepare("INSERT INTO order_items (order_id,product_id,product_name,color,size,price,quantity) VALUES (?,?,?,?,?,?,?)");
      foreach ($rows as $r) $ins->execute([$oid, $r['p']['id'], $r['p']['name'], $r['color'], $r['size'], $r['p']['price'], $r['qty']]);
      $pdo->commit();
      unset($_SESSION['cart'], $_SESSION['coupon']);
      header('Location: success.php?no=' . $orderNo); exit;
    } catch (Exception $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $err = 'Could not place the order. Please try again.';
    }
  }
}
$def = $addrs[0] ?? null;
$selPay = $_POST['payment'] ?? 'bank_transfer';
$selDel = $_POST['delivery'] ?? 'regular';
include 'header.php';
?>
<div class="ct">
  <div class="bc"><a href="index.php">Home</a> › <a href="cart.php">Cart</a> › Checkout</div>
  <h2 style="font-size:24px;margin:8px 0 18px">Checkout</h2>
  <?php if ($err): ?><p class="pf-er"><?= htmlspecialchars($err) ?></p><?php endif; ?>

  <form method="post" class="ck">
    <div>
      <div class="ck-box">
        <h4><span>1</span> Customer Information</h4>
        <p class="ck-ci">
          ✓ <b><?= htmlspecialchars($user['full_name']) ?></b><br>
          ✓ <?= htmlspecialchars($user['email']) ?><br>
          ✓ <?= htmlspecialchars($user['phone'] ?: 'No phone yet') ?>
        </p>
      </div>

      <div class="ck-box">
        <h4><span>2</span> Shipping Address
          <?php if ($addrs): ?><a href="#" id="chg" onclick="showAddr();return false">Change</a><?php endif; ?>
        </h4>

        <?php if ($addrs): ?>
          <div id="addrSum" class="ck-ad">
            <b><?= htmlspecialchars($def['label']) ?></b><br>
            <?= htmlspecialchars($def['address_line']) ?><?= $def['city'] ? ', ' . htmlspecialchars($def['city']) : '' ?><br>
            <small><?= htmlspecialchars($def['phone'] ?? '') ?></small>
          </div>
          <div id="addrList" style="display:none">
            <?php foreach ($addrs as $i => $x): ?>
              <label class="ck-opt">
                <input type="radio" name="address_id" value="<?= $x['id'] ?>" <?= $i === 0 ? 'checked' : '' ?> onchange="newF(false)">
                <span><b><?= htmlspecialchars($x['label']) ?></b> — <?= htmlspecialchars($x['address_line']) ?><?= $x['city'] ? ', ' . htmlspecialchars($x['city']) : '' ?></span>
              </label>
            <?php endforeach; ?>
            <label class="ck-opt">
              <input type="radio" name="address_id" value="new" onchange="newF(true)">
              <span>+ Use a new address</span>
            </label>
          </div>
        <?php else: ?>
          <input type="hidden" name="address_id" value="new">
        <?php endif; ?>

        <div id="newF" style="<?= $addrs ? 'display:none' : '' ?>">
          <input class="pf-in" name="address_line" placeholder="Street address" value="<?= htmlspecialchars($_POST['address_line'] ?? '') ?>">
          <input class="pf-in" name="city" placeholder="City" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
          <input class="pf-in" name="phone" placeholder="Phone" value="<?= htmlspecialchars($_POST['phone'] ?? ($user['phone'] ?? '')) ?>">
        </div>
      </div>

      <div class="ck-box">
        <h4><span>3</span> Delivery Option</h4>
        <label class="ck-opt">
          <input type="radio" name="delivery" value="regular" data-fee="<?= $fees['regular'] ?>" <?= $selDel !== 'express' ? 'checked' : '' ?> onchange="calc()">
          <span>Regular (3–7 days)</span><em><?= money($fees['regular']) ?></em>
        </label>
        <label class="ck-opt">
          <input type="radio" name="delivery" value="express" data-fee="<?= $fees['express'] ?>" <?= $selDel === 'express' ? 'checked' : '' ?> onchange="calc()">
          <span>Express (1–3 days)</span><em><?= money($fees['express']) ?></em>
        </label>
      </div>

      <div class="ck-box">
        <h4><span>4</span> Payment Method</h4>
        <label class="ck-opt"><input type="radio" name="payment" value="bank_transfer" <?= $selPay === 'bank_transfer' ? 'checked' : '' ?>><span>Bank Transfer</span></label>
        <label class="ck-opt"><input type="radio" name="payment" value="cod" <?= $selPay === 'cod' ? 'checked' : '' ?>><span>COD (Cash on Delivery)</span></label>
        <label class="ck-opt"><input type="radio" name="payment" value="ewallet" <?= $selPay === 'ewallet' ? 'checked' : '' ?>><span>E-Wallet (OVO, GoPay, Dana)</span></label>
      </div>
    </div>

    <aside class="ck-sum">
      <h4>Order Summary</h4>
      <?php foreach ($rows as $r): ?>
        <div class="ck-it">
          <div class="ca-img" style="width:54px;height:66px">
            <?php if ($r['p']['main_image']): ?><img src="<?= htmlspecialchars($r['p']['main_image']) ?>" alt="">
            <?php else: ?><span><?= htmlspecialchars(mb_substr($r['p']['name'], 0, 1)) ?></span><?php endif; ?>
          </div>
          <div><b><?= htmlspecialchars($r['p']['name']) ?></b><small>× <?= $r['qty'] ?></small></div>
          <span><?= money($r['p']['price'] * $r['qty']) ?></span>
        </div>
      <?php endforeach; ?>

      <div class="ck-ln"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
      <?php if ($disc): ?><div class="ck-ln"><span>Discount</span><span>- <?= money($disc) ?></span></div><?php endif; ?>
      <div class="ck-ln"><span>Shipping</span><span id="shipV"></span></div>
      <div class="ck-ln tot"><span>Total</span><span id="totV"></span></div>

      <button class="btn">Place Order</button>
      <p class="ck-safe">🔒 Your information is safe and secure</p>
    </aside>
  </form>
</div>
<script>
const BASE = <?= (int)($subtotal - $disc) ?>;
const fmt = n => 'Rp ' + n.toLocaleString('id-ID');
function calc(){
  const fee = +document.querySelector('input[name=delivery]:checked').dataset.fee;
  document.getElementById('shipV').textContent = fmt(fee);
  document.getElementById('totV').textContent = fmt(BASE + fee);
}
function showAddr(){
  document.getElementById('addrSum').style.display = 'none';
  document.getElementById('addrList').style.display = 'block';
  document.getElementById('chg').style.display = 'none';
}
function newF(on){ document.getElementById('newF').style.display = on ? 'block' : 'none'; }
calc();
</script>
<?php include 'footer.php'; ?>