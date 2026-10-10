<?php
require 'db.php';
if (empty($_SESSION['user_id'])) { header('Location: login.php?next=track.php'); exit; }
$uid = (int)$_SESSION['user_id'];

$order = null; $items = []; $err = '';
$id = (int)($_GET['id'] ?? 0);
$no = trim($_GET['no'] ?? '');

if ($id || $no !== '') {
  $sql = "SELECT o.*, a.label, a.address_line, a.city, a.phone AS aphone
          FROM orders o LEFT JOIN addresses a ON a.id=o.address_id
          WHERE o.user_id=? AND " . ($id ? "o.id=?" : "o.order_no=?");
  $st = $pdo->prepare($sql);
  $st->execute([$uid, $id ?: $no]);
  $order = $st->fetch();
  if ($order) {
    $it = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?");
    $it->execute([$order['id']]);
    $items = $it->fetchAll();
  } else {
    $err = 'Order not found. Please check the order number.';
  }
}

$active = [];
if (!$order) {
  $st = $pdo->prepare("SELECT * FROM orders WHERE user_id=? AND status IN ('processing','shipped') ORDER BY id DESC");
  $st->execute([$uid]);
  $active = $st->fetchAll();
}

$payLabel = ['bank_transfer' => 'Bank Transfer', 'cod' => 'Cash on Delivery', 'ewallet' => 'E-Wallet'];
include 'header.php';
?>
<div class="ct">
  <div class="bc"><a href="index.php">Home</a> › Track Order</div>
  <div class="pf-wrap">
    <?php include 'account_nav.php'; ?>
    <div>
      <div class="pf-card">
        <div class="pf-head"><h3>Track Order</h3></div>
        <form method="get" class="tk-search">
          <input name="no" placeholder="Enter order number (e.g. MN2610075927)" value="<?= htmlspecialchars($no) ?>">
          <button class="btn">Track</button>
        </form>
        <?php if ($err): ?><p class="pf-er" style="margin-top:14px"><?= htmlspecialchars($err) ?></p><?php endif; ?>
      </div>

      <?php if ($order):
        $map  = ['processing' => 1, 'shipped' => 2, 'delivered' => 3];
        $cur  = $map[$order['status']] ?? -1;
        $days = $order['delivery_method'] === 'express' ? [1, 3] : [3, 7];
        $from = strtotime($order['created_at'] . " +{$days[0]} days");
        $to   = strtotime($order['created_at'] . " +{$days[1]} days");
        $steps = [
          ['Order Placed', date('d M Y, H:i', strtotime($order['created_at'])), 'We received your order.'],
          ['Processing',   '',                                                   'We are preparing your items.'],
          ['Shipped',      '',                                                   'Your order is on the way.'],
          ['Delivered',    '',                                                   'Your order has arrived.'],
        ];
      ?>
      <div class="pf-card">
        <div class="tk-top">
          <div>
            <b>Order #<?= htmlspecialchars($order['order_no']) ?></b>
            <small><?= date('d M Y', strtotime($order['created_at'])) ?></small>
          </div>
          <span class="od-st <?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
        </div>

        <?php if ($order['status'] === 'cancelled'): ?>
          <p class="pf-er">This order was cancelled.</p>
        <?php else: ?>
          <div class="tk-tl">
            <?php foreach ($steps as $i => $s):
              $cls = $i < $cur ? 'done' : ($i === $cur ? 'now' : '');
              if ($i === 0) $cls = 'done';
            ?>
              <div class="tk-s <?= $cls ?>">
                <i><?= $cls === 'done' ? '✓' : '' ?></i>
                <div>
                  <b><?= $s[0] ?></b>
                  <?php if ($s[1]): ?><small><?= $s[1] ?></small><?php endif; ?>
                  <?php if ($cls): ?><small><?= $s[2] ?></small><?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <?php if ($order['status'] !== 'delivered'): ?>
            <p class="tk-eta">📅 Estimated delivery: <b><?= date('d M', $from) ?> – <?= date('d M Y', $to) ?></b></p>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <div class="pf-card">
        <div class="pf-head"><h3>Order Details</h3></div>
        <table class="od-tb">
          <?php foreach ($items as $i): ?>
            <tr>
              <td><?= htmlspecialchars($i['product_name']) ?> <small>(<?= htmlspecialchars($i['color'] ?? '') ?> / <?= htmlspecialchars($i['size'] ?? '') ?>)</small></td>
              <td>× <?= $i['quantity'] ?></td>
              <td><?= money($i['price'] * $i['quantity']) ?></td>
            </tr>
          <?php endforeach; ?>
          <tr><td colspan="2">Shipping (<?= ucfirst($order['delivery_method']) ?>)</td><td><?= money($order['shipping_fee']) ?></td></tr>
          <tr><td colspan="2"><b>Total</b></td><td><b><?= money($order['total']) ?></b></td></tr>
        </table>
        <div class="tk-info">
          <div>
            <small>Shipping Address</small>
            <p><?= htmlspecialchars($order['label'] ?? '-') ?><br>
               <?= htmlspecialchars($order['address_line'] ?? '') ?><?= !empty($order['city']) ? ', ' . htmlspecialchars($order['city']) : '' ?><br>
               <?= htmlspecialchars($order['aphone'] ?? '') ?></p>
          </div>
          <div>
            <small>Payment</small>
            <p><?= $payLabel[$order['payment_method']] ?? $order['payment_method'] ?><br>
               <span class="od-st <?= ($order['payment_status'] ?? 'pending') === 'paid' ? 'delivered' : 'processing' ?>"><?= ucfirst($order['payment_status'] ?? 'pending') ?></span></p>
          </div>
        </div>
        <a class="btn out" href="track.php" style="display:inline-block;width:auto;padding:10px 20px;margin-top:6px">← Back</a>
      </div>

      <?php elseif (!$err): ?>
      <div class="pf-card">
        <div class="pf-head"><h3>Active Orders</h3></div>
        <?php if (!$active): ?>
          <p style="color:var(--grey);font-size:14px">No orders on the way right now. <a href="orders.php" style="color:var(--pink);font-weight:600">View order history →</a></p>
        <?php endif; ?>
        <?php foreach ($active as $o): ?>
          <div class="od"><div class="od-row" style="grid-template-columns:1.2fr 1fr 1fr auto">
            <div><b>Order #<?= htmlspecialchars($o['order_no']) ?></b><small><?= date('d M Y', strtotime($o['created_at'])) ?></small></div>
            <span class="od-st <?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span>
            <b><?= money($o['total']) ?></b>
            <a class="btn" href="track.php?id=<?= $o['id'] ?>">Track</a>
          </div></div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include 'footer.php'; ?>