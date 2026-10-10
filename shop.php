<?php
require 'db.php';
function ph($a) { return implode(',', array_fill(0, count($a), '?')); }

$cats  = array_values(array_filter((array)($_GET['cat'] ?? [])));
$sizes = array_values(array_filter((array)($_GET['size'] ?? [])));
$cols  = array_values(array_filter((array)($_GET['color'] ?? [])));
$max   = (int)($_GET['max'] ?? 1000000); if ($max <= 0) $max = 1000000;
$q     = trim($_GET['q'] ?? '');
$sort  = $_GET['sort'] ?? 'pop';

$sql = "SELECT DISTINCT p.* FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.price<=?";
$args = [$max];
if ($cats)  { $sql .= " AND c.slug IN (" . ph($cats) . ")"; $args = array_merge($args, $cats); }
if ($sizes) { $sql .= " AND EXISTS (SELECT 1 FROM product_sizes s WHERE s.product_id=p.id AND s.size IN (" . ph($sizes) . "))"; $args = array_merge($args, $sizes); }
if ($cols)  { $sql .= " AND EXISTS (SELECT 1 FROM product_colors k WHERE k.product_id=p.id AND k.color_name IN (" . ph($cols) . "))"; $args = array_merge($args, $cols); }
if ($q !== '') { $sql .= " AND p.name LIKE ?"; $args[] = "%$q%"; }
$sql .= match ($sort) {
  'low'  => " ORDER BY p.price ASC",
  'high' => " ORDER BY p.price DESC",
  'new'  => " ORDER BY p.id DESC",
  default => " ORDER BY p.is_best_seller DESC, p.rating_avg DESC"
};
$st = $pdo->prepare($sql); $st->execute($args);
$items = $st->fetchAll();

$catList = $pdo->query("SELECT c.*, COUNT(p.id) n FROM categories c LEFT JOIN products p ON p.category_id=c.id GROUP BY c.id")->fetchAll();
$colList = $pdo->query("SELECT color_name, MIN(hex_code) hex_code FROM product_colors GROUP BY color_name")->fetchAll();
include 'header.php';
?>
<div class="ct">
  <div class="bc"><a href="index.php">Home</a> › Shop</div>
  <h2 style="font-size:26px;margin:6px 0 14px">Shop</h2>
  <div class="shop2">
    <form class="fl" id="f" method="get">
      <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>"><?php endif; ?>
      <h4>Categories</h4>
      <?php foreach ($catList as $c): ?>
        <label class="ck"><input type="checkbox" name="cat[]" value="<?= $c['slug'] ?>" <?= in_array($c['slug'], $cats) ? 'checked' : '' ?>> <?= htmlspecialchars($c['name']) ?> (<?= $c['n'] ?>)</label>
      <?php endforeach; ?>

      <h4>Price Range</h4>
      <input type="range" name="max" min="50000" max="1000000" step="10000" value="<?= $max ?>"
             oninput="pv.textContent='Up to Rp '+Number(this.value).toLocaleString('id-ID')">
      <output id="pv">Up to Rp <?= number_format($max, 0, ',', '.') ?></output>

      <h4>Size</h4>
      <?php foreach (['XS','S','M','L','XL'] as $s): ?>
        <label class="ck"><input type="checkbox" name="size[]" value="<?= $s ?>" <?= in_array($s, $sizes) ? 'checked' : '' ?>> <?= $s ?></label>
      <?php endforeach; ?>

      <?php if ($colList): ?>
      <h4>Color</h4>
      <div class="cds">
        <?php foreach ($colList as $c): ?>
          <label class="cd" title="<?= htmlspecialchars($c['color_name']) ?>"><input type="checkbox" name="color[]" value="<?= htmlspecialchars($c['color_name']) ?>" <?= in_array($c['color_name'], $cols) ? 'checked' : '' ?>><span style="background:<?= $c['hex_code'] ?>"></span></label>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="fb"><button class="btn">Apply</button><a class="btn out" href="shop.php">Clear</a></div>
    </form>

    <div>
      <div class="tb2">
        <span>Showing <?= count($items) ?> products</span>
        <span>Sort by:
          <select form="f" name="sort" onchange="document.getElementById('f').submit()">
            <option value="pop" <?= $sort=='pop'?'selected':'' ?>>Popular</option>
            <option value="new" <?= $sort=='new'?'selected':'' ?>>Newest</option>
            <option value="low" <?= $sort=='low'?'selected':'' ?>>Price: Low → High</option>
            <option value="high" <?= $sort=='high'?'selected':'' ?>>Price: High → Low</option>
          </select>
        </span>
      </div>
      <div class="pg"><?php foreach ($items as $p) echo card($p); ?></div>
      <?php if (!$items) echo '<p style="padding:30px 0;color:#888">No products found.</p>'; ?>
    </div>
  </div>
</div>
<?php include 'footer.php'; ?>