<?php
require 'db.php';
$pid = (int)($_POST['product_id'] ?? 0);
if (empty($_SESSION['user_id'])) { header('Location: login.php?next=index.php'); exit; }

$rating  = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');
if ($pid && $rating >= 1 && $rating <= 5) {
  $uid = $_SESSION['user_id'];
  $chk = $pdo->prepare("SELECT id FROM reviews WHERE product_id=? AND user_id=?");
  $chk->execute([$pid, $uid]);
  if ($old = $chk->fetchColumn()) {
    $pdo->prepare("UPDATE reviews SET rating=?, comment=? WHERE id=?")->execute([$rating, $comment, $old]);
  } else {
    $pdo->prepare("INSERT INTO reviews (product_id,user_id,rating,comment) VALUES (?,?,?,?)")->execute([$pid, $uid, $rating, $comment]);
  }
  recalc_rating($pdo, $pid);
}
header('Location: product.php?id=' . $pid . '#reviews'); exit;