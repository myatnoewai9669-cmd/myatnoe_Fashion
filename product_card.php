<?php
require 'db.php';
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT * FROM products WHERE id=?"); $st->execute([$id]);
$p = $st->fetch();
if (!$p) { header('Location: shop.php'); exit; }
$colors = $pdo->prepare("SELECT * FROM product_colors WHERE product_id=?"); $colors->execute([$id]); $colors = $colors->fetchAll();
$sizes  = $pdo->prepare("SELECT * FROM product_sizes WHERE product_id=?");  $sizes->execute([$id]);  $sizes = $sizes->fetchAll();
include 'includes/header.php';
?>
<section class="wrap detail">
  <div class="thumb big">
    <?php if ($p['main_image']): ?><img src="<?= htmlspecialchars($p['main_image']) ?>"><?php else: ?><div class="ph"><?= mb_substr($p['name'],0,1) ?></div><?php endif; ?>
  </div>
  <form method="post" action="cart.php" class="info">
    <input type="hidden" name="action" value="add">
    <input type="hidden" name="id" value="<?= $p['id'] ?>">
    <h2><?= htmlspecialchars($p['name']) ?></h2>
    <p class="stars"> <small>(<?= $p['rating_avg'] ?>)</small></p>
    <p class="big-price"><?= money($p['price']) ?>
      <?php if ($p['old_price']): ?><s><?= money($p['old_price']) ?></s> <span class="off"><?= $p['discount_percent'] ?>% OFF</span><?php endif; ?>
    </p>

    <?php if ($colors): ?><h4>Color</h4><div class="opts">
      <?php foreach ($colors as $i => $c): ?>
        <label class="dot"><input type="radio" name="color" value="<?= $c['color_name'] ?>" <?= $i==0?'checked':'' ?>><span style="background:<?= $c['hex_code'] ?>"></span></label>
      <?php endforeach; ?></div><?php endif; ?>

    <?php if ($sizes): ?><h4>Size</h4><div class="opts">
      <?php foreach ($sizes as $i => $s): ?>
        <label class="size"><input type="radio" name="size" value="<?= $s['size'] ?>" <?= $i==0?'checked':'' ?>><span><?= $s['size'] ?></span></label>
      <?php endforeach; ?></div><?php endif; ?>

    <h4>Quantity</h4>
    <input type="number" name="qty" value="1" min="1" class="qty">
    <button class="btn full"> Add to Cart</button>
    <h4>Description</h4>
    <p class="desc"><?= nl2br(htmlspecialchars($p['description'])) ?></p>
  </form>
</section>
<?php include 'footer.php'; ?>