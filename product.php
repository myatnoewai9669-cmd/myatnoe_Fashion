<?php
require 'db.php';
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT p.*, c.name AS cat FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.id=?");
$st->execute([$id]);
$p = $st->fetch();
if (!$p) { header('Location: shop.php'); exit; }

$colors = $pdo->prepare("SELECT * FROM product_colors WHERE product_id=?"); $colors->execute([$id]); $colors = $colors->fetchAll();
$sizes  = $pdo->prepare("SELECT * FROM product_sizes WHERE product_id=?");  $sizes->execute([$id]);  $sizes = $sizes->fetchAll();
$extra  = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id=?"); $extra->execute([$id]);
$gallery = array_values(array_filter(array_merge([$p['main_image']], $extra->fetchAll(PDO::FETCH_COLUMN))));
$rv = $pdo->prepare("SELECT r.*, u.full_name FROM reviews r JOIN users u ON u.id=r.user_id WHERE r.product_id=? ORDER BY r.id DESC");
$rv->execute([$id]); $reviews = $rv->fetchAll();
include 'header.php';
?>
<div class="ct">
  <div class="bc"><a href="index.php">Home</a>  <a href="shop.php">Shop</a>  <?= htmlspecialchars($p['name']) ?></div>
  <div class="pd">
    <div class="th">
      <?php foreach ($gallery as $i => $g): ?>
        <div class="<?= $i==0?'on':'' ?>" onclick="pick(this,'<?= htmlspecialchars($g, ENT_QUOTES) ?>')"><img src="<?= htmlspecialchars($g) ?>"></div>
      <?php endforeach; ?>
    </div>
    <div class="mi">
      <?php if ($gallery): ?><img id="mainimg" src="<?= htmlspecialchars($gallery[0]) ?>"><?php else: ?><div class="ph"><?= htmlspecialchars(mb_substr($p['name'], 0, 1)) ?></div><?php endif; ?>
    </div>

    <form class="pi" method="post" action="cart.php">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="id" value="<?= $p['id'] ?>">
      <h1><?= htmlspecialchars($p['name']) ?></h1>
      <p class="st2"><?= stars($p['rating_avg']) ?> <small>(<?= $p['rating_avg'] ?>)  <?= count($reviews) ?> reviews</small></p>
      <p class="pp"><?= money($p['price']) ?>
        <?php if ($p['old_price']): ?><s><?= money($p['old_price']) ?></s><span class="off2"><?= $p['discount_percent'] ?>% OFF</span><?php endif; ?>
      </p>

      <?php if ($colors): ?><h5>Color</h5><div class="op">
        <?php foreach ($colors as $i => $c): ?>
          <label class="cd" title="<?= htmlspecialchars($c['color_name']) ?>"><input type="radio" name="color" value="<?= htmlspecialchars($c['color_name']) ?>" <?= $i==0?'checked':'' ?>><span style="background:<?= $c['hex_code'] ?>;width:28px;height:28px"></span></label>
        <?php endforeach; ?></div><?php endif; ?>

      <?php if ($sizes): ?><h5>Size</h5><div class="op">
        <?php foreach ($sizes as $i => $s): ?>
          <label class="sz"><input type="radio" name="size" value="<?= $s['size'] ?>" <?= $i==0?'checked':'' ?>><span><?= $s['size'] ?></span></label>
        <?php endforeach; ?></div><?php endif; ?>

      <h5>Quantity</h5>
      <div class="qb">
        <button type="button" onclick="q.value=Math.max(1,+q.value-1)"></button>
        <input id="q" name="qty" value="1" readonly>
        <button type="button" onclick="q.value=+q.value+1">+</button>
      </div>

      <button class="btn"> Add to Cart</button>
      <button class="btn out" formaction="wishlist.php" style="border-color:var(--line);color:#444"><?= in_array((int)$p['id'], $WISH) ? '♥ Remove from Wishlist' : '♡ Add to Wishlist' ?></button>
      <button class="btn out" name="buy" value="1">Buy Now</button>
    </form>
  </div>

  <div class="tabs">
    <button type="button" class="on" onclick="tab(this,'t1')">Description</button>
    <button type="button" onclick="tab(this,'t2')">Reviews (<?= count($reviews) ?>)</button>
  </div>
  <div class="tp" id="t1"><?= nl2br(htmlspecialchars($p['description'] ?: 'No description yet.')) ?></div>
  <div class="tp" id="t2" style="display:none">
    <?php if (!empty($_SESSION['user_id'])): ?>
      <form method="post" action="review_add.php" class="rf">
        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
        <b>Write a review</b>
        <select name="rating">
          <option value="5"> (5)</option><option value="4"> (4)</option>
          <option value="3"> (3)</option><option value="2"> (2)</option><option value="1"> (1)</option>
        </select>
        <textarea name="comment" rows="3" placeholder="Share your experience..."></textarea>
        <button class="btn" style="width:auto;display:inline-block;padding:10px 22px;margin:0">Submit Review</button>
      </form>
    <?php else: ?>
      <p><a href="login.php" style="color:var(--pink);font-weight:600">Login</a> to write a review.</p>
    <?php endif; ?>

    <?php if (!$reviews) echo '<p>No reviews yet.</p>'; ?>
    <?php foreach ($reviews as $r): ?>
      <div class="rv"><b><?= htmlspecialchars($r['full_name']) ?></b> <span class="st2"><?= stars($r['rating']) ?></span><br><?= htmlspecialchars($r['comment']) ?></div>
    <?php endforeach; ?>
  </div>
</div>
<script>
function pick(el, src){
  document.querySelectorAll('.th div').forEach(d=>d.classList.remove('on'));
  el.classList.add('on');
  document.getElementById('mainimg').src = src;
}
function tab(btn, id){
  document.querySelectorAll('.tabs button').forEach(b=>b.classList.remove('on'));
  btn.classList.add('on');
  document.getElementById('t1').style.display = id=='t1' ? 'block' : 'none';
  document.getElementById('t2').style.display = id=='t2' ? 'block' : 'none';
}
if (location.hash === '#reviews') tab(document.querySelectorAll('.tabs button')[1], 't2');
</script>
<?php include 'footer.php'; ?>