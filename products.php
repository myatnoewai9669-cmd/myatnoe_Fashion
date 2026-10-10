<?php
require 'db.php';
if (($_SESSION['role'] ?? '') !== 'admin') { header('Location: login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
  $id = (int)$_POST['delete'];
  $st = $pdo->prepare("SELECT main_image FROM products WHERE id=?"); $st->execute([$id]);
  $img = $st->fetchColumn();
  $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
  if ($img && is_file(ROOT_DIR . '/' . $img)) @unlink(ROOT_DIR . '/' . $img);
  header('Location: products.php?msg=deleted'); exit;
}
$items = $pdo->query("SELECT p.*, c.name AS cat FROM products p LEFT JOIN categories c ON c.id=p.category_id ORDER BY p.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Products - Admin</title>
<link rel="stylesheet" href="admin.css">
</head><body>
<div class="adm">
  <?php include 'side.php'; ?>
  <main class="main">
    <div class="top"><h2>Products</h2><a class="sv" href="product_form.php">+ Add Product</a></div>
    <?php if (isset($_GET['msg'])): ?>
      <p class="okmsg"><?= $_GET['msg'] === 'deleted' ? 'Product deleted.' : 'Saved successfully.' ?></p>
    <?php endif; ?>
    <div class="box">
      <table class="tb">
        <tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Tags</th><th></th></tr>
        <?php foreach ($items as $p): ?>
        <tr>
          <td><?= $p['main_image']
                ? '<img class="ts" src="' . BASE_URL . '/' . htmlspecialchars($p['main_image']) . '" alt="">'
                : '<div class="ts ph">' . htmlspecialchars(mb_substr($p['name'], 0, 1)) . '</div>' ?></td>
          <td><b><?= htmlspecialchars($p['name']) ?></b></td>
          <td><?= htmlspecialchars($p['cat'] ?? '-') ?></td>
          <td><?= money($p['price']) ?></td>
          <td><?= $p['stock'] ?></td>
          <td><?= $p['is_new'] ? '<span class="pill shipped">New</span> ' : '' ?><?= $p['is_best_seller'] ? '<span class="pill delivered">Best</span>' : '' ?></td>
          <td class="acts">
            <a class="ed" href="product_form.php?id=<?= $p['id'] ?>">Edit</a>
            <form method="post" onsubmit="return confirm('Delete this product?')">
              <button class="dl" name="delete" value="<?= $p['id'] ?>">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </main>
</div>
</body></html>