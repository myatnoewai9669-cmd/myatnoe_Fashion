<?php
require 'db.php';
$new  = $pdo->query("SELECT * FROM products WHERE is_new=1 ORDER BY id DESC LIMIT 4")->fetchAll();
$best = $pdo->query("SELECT * FROM products WHERE is_best_seller=1 ORDER BY rating_avg DESC LIMIT 4")->fetchAll();
$pics = $pdo->query("SELECT main_image FROM products WHERE main_image IS NOT NULL AND main_image<>'' ORDER BY id DESC LIMIT 4")->fetchAll(PDO::FETCH_COLUMN);

$slides = [
  ['a' => 'New Season', 'b' => 'New Style',  'p' => 'Discover the latest fashion trends and express your unique style.', 'img' => 'assets/hero1.jpg', 'btn' => 'Shop Now',  'link' => 'shop.php?sort=new'],
  ['a' => 'Summer',     'b' => 'Collection', 'p' => 'Light, fresh and made for sunny days.',                            'img' => 'assets/hero2.jpg', 'btn' => 'Explore',   'link' => 'shop.php'],
  ['a' => 'Up to 50%',  'b' => 'OFF',        'p' => 'Limited-time deals on selected items.',                             'img' => 'assets/hero3.jpg', 'btn' => 'Shop Sale', 'link' => 'shop.php'],
];
foreach ($slides as $i => &$s) {
  $s['src'] = is_file(__DIR__ . '/' . $s['img']) ? $s['img'] : ($pics[$i] ?? '');
}
unset($s);
$promo = is_file(__DIR__ . '/assets/promo.jpg') ? 'assets/promo.jpg' : ($pics[3] ?? ($pics[0] ?? ''));
include 'header.php';
?>
<style>
.sl{position:relative;height:420px;background:linear-gradient(110deg,#f6c3d2 0%,#fde4ec 100%);overflow:hidden}
.sl-s{position:absolute;inset:0;display:grid;grid-template-columns:1fr 1fr;opacity:0;visibility:hidden;transition:opacity .6s}
.sl-s.on{opacity:1;visibility:visible}
.sl-c{align-self:center;padding:0 10% 0 9%;position:relative;z-index:2}
.sl-c h1{font-size:clamp(34px,5vw,56px);line-height:1.1;margin-bottom:14px;color:#1f2330}
.sl-c p{color:#6b5560;max-width:340px;line-height:1.6;margin-bottom:22px;font-size:14px}
.sl-c .btn{display:inline-block;padding:12px 28px;width:auto}
.sl-v{height:100%;background-size:cover;background-position:center 18%;background-repeat:no-repeat;
  -webkit-mask-image:linear-gradient(90deg,transparent 0,#000 28%);mask-image:linear-gradient(90deg,transparent 0,#000 28%)}
.sl-ph{display:grid;place-items:center;-webkit-mask-image:none;mask-image:none}
.sl-ph span{width:240px;height:240px;border-radius:50%;background:rgba(232,51,109,.18);display:grid;place-items:center;font-size:110px}
.sl-a{position:absolute;top:50%;transform:translateY(-50%);width:36px;height:36px;border-radius:50%;border:0;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,.18);cursor:pointer;font-size:20px;line-height:1;z-index:5}
.sl-a.l{left:16px}.sl-a.r{right:16px}
.sl-d{position:absolute;bottom:16px;left:50%;transform:translateX(-50%);display:flex;gap:8px;z-index:5}
.sl-d i{width:8px;height:8px;border-radius:8px;background:#fff;opacity:.85;cursor:pointer;transition:all .25s}
.sl-d i.on{background:#e8336d;opacity:1;width:22px}
.pm{margin:38px 5%;border-radius:16px;background:linear-gradient(100deg,#f8c9d6,#f6a1b8);display:grid;grid-template-columns:1fr 1fr;align-items:center;overflow:hidden;min-height:170px}
.pm>div:first-child{padding:30px 40px}
.pm h3{font-size:28px;color:#1f2330}
.pm p{color:#6b4a55;margin:4px 0 16px}
.pm .btn{display:inline-block;padding:11px 26px;width:auto}
.pm-v{height:100%;min-height:170px;background-size:cover;background-position:center 20%;display:grid;place-items:center;font-size:80px;
  -webkit-mask-image:linear-gradient(90deg,transparent 0,#000 30%);mask-image:linear-gradient(90deg,transparent 0,#000 30%)}
@media(max-width:800px){
  .sl{height:500px}
  .sl-s{grid-template-columns:1fr;grid-template-rows:auto 1fr}
  .sl-c{padding:30px 7% 0}
  .sl-v{-webkit-mask-image:none;mask-image:none;min-height:220px}
  .pm{grid-template-columns:1fr}
  .pm-v{display:none}
}
</style>

<section class="sl" id="sl">
  <?php foreach ($slides as $i => $s): ?>
    <div class="sl-s <?= $i === 0 ? 'on' : '' ?>">
      <div class="sl-c">
        <h1><?= $s['a'] ?><br><?= $s['b'] ?></h1>
        <p><?= $s['p'] ?></p>
        <a class="btn" href="<?= $s['link'] ?>"><?= $s['btn'] ?></a>
      </div>
      <?php if ($s['src']): ?>
        <div class="sl-v" style="background-image:url('<?= htmlspecialchars($s['src']) ?>')"></div>
      <?php else: ?>
        <div class="sl-v sl-ph"><span>👗</span></div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  <button class="sl-a l" type="button" onclick="go(-1)">‹</button>
  <button class="sl-a r" type="button" onclick="go(1)">›</button>
  <div class="sl-d"><?php foreach ($slides as $i => $s): ?><i class="<?= $i === 0 ? 'on' : '' ?>" onclick="show(<?= $i ?>)"></i><?php endforeach; ?></div>
</section>

<section class="perks2">
  <div><span>🚚</span><p><b>Free Shipping</b><small>On orders over Rp 500.000</small></p></div>
  <div><span>🔒</span><p><b>Secure Payment</b><small>100% secure payment</small></p></div>
  <div><span>↩</span><p><b>Easy Return</b><small>7 days return</small></p></div>
</section>

<div class="ct">
  <div class="sec"><h2>New Arrivals</h2><a href="shop.php?sort=new">View All →</a></div>
  <div class="pg"><?php foreach ($new as $p) echo card($p); ?></div>
  <?php if (!$new) echo '<p style="color:#888">No new arrivals yet.</p>'; ?>

  <div class="sec"><h2>Best Sellers</h2><a href="shop.php">View All →</a></div>
  <div class="pg"><?php foreach ($best as $p) echo card($p); ?></div>
  <?php if (!$best) echo '<p style="color:#888">No best sellers yet.</p>'; ?>
</div>

<section class="pm">
  <div>
    <h3>Up to 50% OFF</h3>
    <p>On Selected Items</p>
    <a class="btn" href="shop.php">Shop Sale →</a>
  </div>
  <?php if ($promo): ?>
    <div class="pm-v" style="background-image:url('<?= htmlspecialchars($promo) ?>')"></div>
  <?php else: ?>
    <div class="pm-v">🛍️</div>
  <?php endif; ?>
</section>

<script>
const slides = document.querySelectorAll('.sl-s'), dots = document.querySelectorAll('.sl-d i');
let cur = 0, timer;
function show(n){
  cur = (n + slides.length) % slides.length;
  slides.forEach((s, i) => s.classList.toggle('on', i === cur));
  dots.forEach((d, i) => d.classList.toggle('on', i === cur));
}
function go(d){ show(cur + d); reset(); }
function reset(){ clearInterval(timer); timer = setInterval(() => show(cur + 1), 5000); }
reset();
</script>
<?php include 'footer.php'; ?>