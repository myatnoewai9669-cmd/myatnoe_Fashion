<?php
require 'db.php';
if (($_SESSION['role'] ?? '') !== 'admin') { header('Location: login.php'); exit; }

function q($pdo, $sql, $args = []) { $s = $pdo->prepare($sql); $s->execute($args); return $s->fetchColumn(); }
function pct($now, $prev) { if ($prev == 0) return $now > 0 ? 100 : 0; return round(($now - $prev) / $prev * 100); }
function chg($p) { return '<span class="' . ($p >= 0 ? 'up' : 'down') . '">' . ($p >= 0 ? '↑ ' : '↓ ') . abs($p) . '%</span>'; }
function short($n) { return $n >= 1e6 ? round($n/1e6, 1) . 'M' : ($n >= 1e3 ? round($n/1e3) . 'K' : $n); }

$thisM = date('Y-m'); $lastM = date('Y-m', strtotime('first day of last month'));
$fmt = "DATE_FORMAT(created_at,'%Y-%m')=?";

$sales  = q($pdo, "SELECT COALESCE(SUM(total),0) FROM orders WHERE status<>'cancelled'");
$orders = q($pdo, "SELECT COUNT(*) FROM orders");
$cust   = q($pdo, "SELECT COUNT(*) FROM users WHERE role='customer'");
$prods  = q($pdo, "SELECT COUNT(*) FROM products");

$pSales = pct(q($pdo,"SELECT COALESCE(SUM(total),0) FROM orders WHERE status<>'cancelled' AND $fmt",[$thisM]), q($pdo,"SELECT COALESCE(SUM(total),0) FROM orders WHERE status<>'cancelled' AND $fmt",[$lastM]));
$pOrd   = pct(q($pdo,"SELECT COUNT(*) FROM orders WHERE $fmt",[$thisM]), q($pdo,"SELECT COUNT(*) FROM orders WHERE $fmt",[$lastM]));
$pCust  = pct(q($pdo,"SELECT COUNT(*) FROM users WHERE role='customer' AND $fmt",[$thisM]), q($pdo,"SELECT COUNT(*) FROM users WHERE role='customer' AND $fmt",[$lastM]));
$pProd  = pct(q($pdo,"SELECT COUNT(*) FROM products WHERE $fmt",[$thisM]), q($pdo,"SELECT COUNT(*) FROM products WHERE $fmt",[$lastM]));

// chart: last 9 months
$data = []; $labels = [];
for ($i = 8; $i >= 0; $i--) {
  $t = strtotime("first day of -$i month");
  $labels[] = date('M', $t);
  $data[] = (float) q($pdo, "SELECT COALESCE(SUM(total),0) FROM orders WHERE status<>'cancelled' AND $fmt", [date('Y-m', $t)]);
}
$max = max($data); $step = $max > 0 ? pow(10, floor(log10($max))) : 1;
$niceMax = $max > 0 ? ceil($max / $step) * $step : 1;
$W = 620; $H = 250; $pl = 58; $pr = 14; $pt = 14; $pb = 30;
$pts = [];
foreach ($data as $i => $v) {
  $x = $pl + $i * ($W - $pl - $pr) / 8;
  $y = $pt + (1 - $v / $niceMax) * ($H - $pt - $pb);
  $pts[] = round($x, 1) . ',' . round($y, 1);
}
$line = implode(' ', $pts);
$area = $pl . ',' . ($H - $pb) . ' ' . $line . ' ' . ($W - $pr) . ',' . ($H - $pb);

$recent = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 4")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Dashboard – Myat Noe Fashion</title>
<link rel="stylesheet" href="admin.css">
<style>
  .dash-two{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(300px,1fr);gap:18px}
  .ro-row{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:6px 12px;padding:12px 0;border-bottom:1px solid #f3f3f6;font-size:13px}
  .ro-row:last-child{border-bottom:0}
  .ro-no{white-space:nowrap}
  .ro-amt{white-space:nowrap}
  .ro-st{font-weight:600}
  .ro-st.processing{color:#d97706}
  .ro-st.shipped{color:#2563eb}
  .ro-st.delivered{color:#16a34a}
  .ro-st.cancelled{color:#dc2626}
  @media(max-width:900px){.dash-two{grid-template-columns:1fr}}
</style>
</head><body>
<div class="adm">
  <?php include 'side.php'; ?>
  <main class="main">
    <h2>Admin Dashboard</h2>

    <div class="cards">
      <div class="card2"><small>Total Sales</small><b><?= money($sales) ?></b><?= chg($pSales) ?></div>
      <div class="card2"><small>Total Orders</small><b><?= $orders ?></b><?= chg($pOrd) ?></div>
      <div class="card2"><small>Total Customers</small><b><?= $cust ?></b><?= chg($pCust) ?></div>
      <div class="card2"><small>Total Products</small><b><?= $prods ?></b><?= chg($pProd) ?></div>
    </div>

    <div class="dash-two">
      <div class="box" id="chart">
        <h3>Sales Analytics</h3>
        <svg viewBox="0 0 <?= $W ?> <?= $H ?>" width="100%" style="min-width:420px">
          <defs><linearGradient id="g" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#e8336d" stop-opacity=".25"/><stop offset="1" stop-color="#e8336d" stop-opacity="0"/>
          </linearGradient></defs>
          <?php for ($k = 0; $k <= 4; $k++):
            $y = $pt + $k * ($H - $pt - $pb) / 4; $val = $niceMax * (4 - $k) / 4; ?>
            <line x1="<?= $pl ?>" x2="<?= $W - $pr ?>" y1="<?= $y ?>" y2="<?= $y ?>" stroke="#eee"/>
            <text x="<?= $pl - 8 ?>" y="<?= $y + 4 ?>" font-size="10" fill="#999" text-anchor="end">Rp <?= short($val) ?></text>
          <?php endfor; ?>
          <polygon points="<?= $area ?>" fill="url(#g)"/>
          <polyline points="<?= $line ?>" fill="none" stroke="#e8336d" stroke-width="2.5" stroke-linejoin="round"/>
          <?php foreach ($pts as $pt2): [$cx, $cy] = explode(',', $pt2); ?>
            <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="3.5" fill="#fff" stroke="#e8336d" stroke-width="2"/>
          <?php endforeach; ?>
          <?php foreach ($labels as $i => $l): ?>
            <text x="<?= $pl + $i * ($W - $pl - $pr) / 8 ?>" y="<?= $H - 8 ?>" font-size="10" fill="#999" text-anchor="middle"><?= $l ?></text>
          <?php endforeach; ?>
        </svg>
      </div>

      <div class="box">
        <h3>Recent Orders <a href="orders.php">View All</a></h3>
        <?php foreach ($recent as $o): ?>
          <div class="ro-row">
            <span class="ro-no">#<?= htmlspecialchars($o['order_no']) ?></span>
            <span class="ro-st <?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span>
            <b class="ro-amt"><?= money($o['total']) ?></b>
          </div>
        <?php endforeach; ?>
        <?php if (!$recent) echo '<p style="color:#999">No orders yet.</p>'; ?>
      </div>
    </div>
    <p class="foot">© 2026 Myat Noe Fashion Admin Panel</p>
  </main>
</div>
</body></html>