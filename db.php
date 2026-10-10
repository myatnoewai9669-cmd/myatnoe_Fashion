<?php
session_start();
$pdo = new PDO('mysql:host=localhost;dbname=myatnoe_fashion;charset=utf8mb4', 'root', '', [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);
define('ROOT_DIR', str_replace('\\', '/', __DIR__));
$__doc = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
define('BASE_URL', implode('/', array_map('rawurlencode', explode('/', substr(ROOT_DIR, strlen($__doc))))));

// Wishlist: product ids saved by the logged-in user
$WISH = [];
if (!empty($_SESSION['user_id'])) {
  $w = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id=?");
  $w->execute([$_SESSION['user_id']]);
  $WISH = array_map('intval', $w->fetchAll(PDO::FETCH_COLUMN));
}

function money($n) { return 'Rp ' . number_format($n, 0, ',', '.'); }

function stars($r) { $f = (int)round($r); return str_repeat('★', $f) . str_repeat('☆', 5 - $f); }

function img_tag($p) {
  return $p['main_image']
    ? '<img src="' . htmlspecialchars($p['main_image']) . '" alt="">'
    : '<div class="ph">' . htmlspecialchars(mb_substr($p['name'], 0, 1)) . '</div>';
}

function card($p) {
  global $WISH;
  $on = in_array((int)$p['id'], $WISH);
  return '<a class="pc" href="product.php?id=' . $p['id'] . '">'
    . '<div class="pimg">' . img_tag($p)
    . '<span class="heart' . ($on ? ' on' : '') . '" onclick="event.preventDefault();event.stopPropagation();wl(this,' . (int)$p['id'] . ')">' . ($on ? '♥' : '♡') . '</span></div>'
    . '<h4>' . htmlspecialchars($p['name']) . '</h4>'
    . '<p class="pr">' . money($p['price']) . '</p>'
    . '<p class="st2">' . stars($p['rating_avg']) . ' <small>(' . $p['rating_avg'] . ')</small></p></a>';
}

function recalc_rating($pdo, $pid) {
  $pdo->prepare("UPDATE products SET rating_avg = COALESCE((SELECT ROUND(AVG(rating),1) FROM reviews WHERE product_id=?),0) WHERE id=?")
      ->execute([$pid, $pid]);
}