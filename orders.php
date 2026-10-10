<?php
require 'db.php';
if (empty($_SESSION['user_id'])) { header('Location: login.php?next=orders.php'); exit; }
$uid = (int)$_SESSION['user_id'];

$st = $pdo->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY id DESC");
$st->execute([$uid]);
$orders = $st->fetchAll();

$it = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?");
$open = (int)($_GET['view'] ?? 0);
$icon = ['processing' => '⏳', 'shipped' => '📦', 'delivered' => '✓', 'cancelled' => '✕'];
include 'header.php';
?>
<div class="ct">
  <div class="bc"><a href="index.php">Home</a> › Order History</div>
  <div class="pf-wrap">
    <?php include 'account_nav.php'; ?>
    <div>
      <div class="pf-card">
        <div class="pf-head"><h3>Order History</h3></div>

        <?php if (!$orders): ?>
          <p style="color:var(--grey);font-size:14px">No orders yet. <a href="shop.php" style="color:var(--pink);font-weight:600">Start shopping →</a></p>
        <?php endif; ?>

        <?php foreach ($orders as $o):
          $it->execute([$o['id']]);
          $items = $it->fetchAll();
          $n = array_sum(array_column($items, 'quantity'));
          $first = $items[0]['product_name'] ?? 'Order';
          $more = count($items) > 1 ? ' + ' . (count($items) - 1) . ' more' : '';
        ?>
        <div class="od">
          <div class="od-row">
            <div>
              <b>Order #<?= htmlspecialchars($o['order_no']) ?></b>
              <small><?= date('d M Y', strtotime($o['created_at'])) ?></small>
            </div>
            <div>
              <span><?= htmlspecialchars($first) . $more ?> (<?= $n ?> item<?= $n > 1 ? 's' : '' ?>)</span>
              <span class="od-st <?= $o['status'] ?>">Status: <?= ucfirst($o['status']) ?> <?= $icon[$o['status']] ?? '' ?></span>
            </div>
            <div class="od-tot">Total: <b><?= money($o['total']) ?></b></div>
            <div>
              <?php if (in_array($o['status'], ['processing', 'shipped'])): ?>
                
              <?php else: ?>
                <a class="<?= $cur=='track.php' ? 'on' : '' ?>" href="track.php"> Track Order</a>
                <a class="btn out" href="orders.php?view=<?= $o['id'] ?>#o<?= $o['id'] ?>">View Details</a>
              <?php endif; ?>
            </div>
          </div>

          <?php if ($open === (int)$o['id']): ?>
          <div class="od-more" id="o<?= $o['id'] ?>">
            <table class="od-tb">
              <?php foreach ($items as $i): ?>
                <tr>
                  <td><?= htmlspecialchars($i['product_name']) ?> <small>(<?= htmlspecialchars($i['color'] ?? '') ?> / <?= htmlspecialchars($i['size'] ?? '') ?>)</small></td>
                  <td>× <?= $i['quantity'] ?></td>
                  <td><?= money($i['price'] * $i['quantity']) ?></td>
                </tr>
              <?php endforeach; ?>
              <tr><td colspan="2">Shipping (<?= $o['delivery_method'] ?>)</td><td><?= money($o['shipping_fee']) ?></td></tr>
              <tr><td colspan="2"><b>Total</b></td><td><b><?= money($o['total']) ?></b></td></tr>
            </table>
            <div class="od-steps">
              <?php
              $flow = ['processing', 'shipped', 'delivered'];
              $pos = array_search($o['status'], $flow);
              foreach ($flow as $k => $s):
                $done = $o['status'] !== 'cancelled' && $pos !== false && $k <= $pos;
              ?>
                <div class="<?= $done ? 'on' : '' ?>"><i></i><span><?= ucfirst($s) ?></span></div>
              <?php endforeach; ?>
            </div>
            <?php if ($o['status'] === 'cancelled'): ?><p style="color:#c0204f;font-size:13px">This order was cancelled.</p><?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<?php include 'footer.php'; ?>