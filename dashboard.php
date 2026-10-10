<?php
require_once __DIR__ . '/config.php';
requireAdmin();

// ---------- Stats ----------
$totalSales     = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status <> 'cancelled'")->fetchColumn();
$totalOrders    = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$totalCustomers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$totalProducts  = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();

// ---------- Monthly sales (last 8 months) ----------
$months = [];
for ($i = 7; $i >= 0; $i--) {
    $key = date('Y-m', strtotime("first day of -$i month"));
    $months[$key] = ['label' => date('M', strtotime($key . '-01')), 'sum' => 0];
}
$rows = $pdo->query(
    "SELECT DATE_FORMAT(created_at,'%Y-%m') AS ym, SUM(total) AS s
     FROM orders
     WHERE status <> 'cancelled'
       AND created_at >= DATE_SUB(DATE_FORMAT(CURDATE(),'%Y-%m-01'), INTERVAL 7 MONTH)
     GROUP BY ym"
)->fetchAll();
foreach ($rows as $r) {
    if (isset($months[$r['ym']])) $months[$r['ym']]['sum'] = (float)$r['s'];
}

// Build SVG chart
$W = 640; $H = 230; $padX = 16; $padTop = 16; $padBottom = 30;
$max = max(array_column($months, 'sum')) ?: 1;
$n = count($months);
$pts = []; $labels = [];
$i = 0;
foreach ($months as $m) {
    $x = $padX + $i * (($W - 2 * $padX) / ($n - 1));
    $y = ($H - $padBottom) - ($m['sum'] / $max) * ($H - $padBottom - $padTop);
    $pts[] = round($x, 1) . ',' . round($y, 1);
    $labels[] = ['x' => round($x, 1), 'text' => $m['label']];
    $i++;
}
$line = implode(' ', $pts);
$baseY = $H - $padBottom;
$firstX = $padX;
$lastX = $W - $padX;
$area = "$firstX,$baseY $line $lastX,$baseY";

// ---------- Recent orders ----------
$recent = $pdo->query(
    'SELECT order_no, status, total FROM orders ORDER BY id DESC LIMIT 5'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Dashboard - <?= e(SITE_NAME) ?></title>
<style>
:root{--primary:#e91e63;--soft:#fce4ec;--text:#222;--muted:#777;--border:#eee}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',Arial,sans-serif;color:var(--text);background:#fafafa;display:flex;min-height:100vh}
a{color:inherit;text-decoration:none}

/* Sidebar */
.side{width:220px;background:#fff;border-right:1px solid var(--border);padding:20px 12px;flex-shrink:0}
.side .logo{font-size:20px;font-weight:700;padding:0 10px 20px}
.side .logo span{color:var(--primary)}
.side a{display:block;padding:10px 12px;border-radius:8px;font-size:14px;margin-bottom:4px}
.side a:hover{background:#f6f6f6}
.side a.active{background:var(--soft);color:var(--primary);font-weight:600}

/* Main */
.main{flex:1;padding:26px 30px;min-width:0}
.main h1{font-size:22px;margin-bottom:18px}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px}
.stat{background:#fff;border:1px solid var(--border);border-radius:12px;padding:16px}
.stat small{color:var(--muted);font-size:12px}
.stat b{display:block;font-size:20px;margin-top:6px}
.cols{display:grid;grid-template-columns:1.6fr 1fr;gap:16px}
.box{background:#fff;border:1px solid var(--border);border-radius:12px;padding:18px}
.box h3{font-size:15px;margin-bottom:12px}
table{width:100%;border-collapse:collapse;font-size:13px}
td{padding:10px 0;border-bottom:1px solid var(--border)}
tr:last-child td{border-bottom:0}
.badge{padding:3px 9px;border-radius:20px;font-size:11px;font-weight:600}
.pending{background:#fff3e0;color:#e65100}
.processing{background:#e3f2fd;color:#1565c0}
.shipped{background:#ede7f6;color:#4527a0}
.delivered{background:#e8f5e9;color:#2e7d32}
.cancelled{background:#ffebee;color:#c62828}
.empty{color:var(--muted);font-size:13px;padding:14px 0}
svg{width:100%;height:auto;display:block}

@media(max-width:900px){
  body{flex-direction:column}
  .side{width:100%;display:flex;flex-wrap:wrap;gap:4px;padding:12px}
  .side .logo{width:100%;padding-bottom:6px}
  .stats{grid-template-columns:repeat(2,1fr)}
  .cols{grid-template-columns:1fr}
}
</style>
</head>
<body>

<aside class="side">
  <div class="logo"><span>Myatnoe</span> Fashion</div>
  <a href="dashboard.php" class="active">Dashboard</a>
  <a href="#">Products</a>
  <a href="#">Categories</a>
  <a href="#">Orders</a>
  <a href="#">Customers</a>
  <a href="#">Payments</a>
  <a href="#">Reviews</a>
  <a href="#">Sales Analytics</a>
  <a href="<?= BASE_URL ?>/logout.php">Logout</a>
</aside>

<main class="main">
  <h1>Admin Dashboard</h1>

  <div class="stats">
    <div class="stat"><small>Total Sales</small><b><?= rupiah($totalSales) ?></b></div>
    <div class="stat"><small>Total Orders</small><b><?= $totalOrders ?></b></div>
    <div class="stat"><small>Total Customers</small><b><?= $totalCustomers ?></b></div>
    <div class="stat"><small>Total Products</small><b><?= $totalProducts ?></b></div>
  </div>

  <div class="cols">
    <div class="box">
      <h3>Sales Analytics</h3>
      <svg viewBox="0 0 <?= $W ?> <?= $H ?>" role="img" aria-label="Monthly sales chart">
        <?php for ($g = 0; $g <= 3; $g++):
            $gy = $padTop + $g * (($H - $padTop - $padBottom) / 3); ?>
          <line x1="<?= $padX ?>" y1="<?= $gy ?>" x2="<?= $W - $padX ?>" y2="<?= $gy ?>" stroke="#eee"/>
        <?php endfor; ?>
        <polygon points="<?= $area ?>" fill="#fce4ec" opacity=".8"/>
        <polyline points="<?= $line ?>" fill="none" stroke="#e91e63" stroke-width="2.5"/>
        <?php foreach ($pts as $p): [$px, $py] = explode(',', $p); ?>
          <circle cx="<?= $px ?>" cy="<?= $py ?>" r="3.5" fill="#e91e63"/>
        <?php endforeach; ?>
        <?php foreach ($labels as $l): ?>
          <text x="<?= $l['x'] ?>" y="<?= $H - 8 ?>" font-size="12" fill="#777" text-anchor="middle"><?= e($l['text']) ?></text>
        <?php endforeach; ?>
      </svg>
    </div>

    <div class="box">
      <h3>Recent Orders</h3>
      <?php if ($recent): ?>
        <table>
          <?php foreach ($recent as $o): ?>
            <tr>
              <td>#<?= e($o['order_no']) ?></td>
              <td><span class="badge <?= e($o['status']) ?>"><?= ucfirst(e($o['status'])) ?></span></td>
              <td style="text-align:right"><?= rupiah($o['total']) ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php else: ?>
        <div class="empty">No orders yet.</div>
      <?php endif; ?>
    </div>
  </div>
</main>

</body>
</html>