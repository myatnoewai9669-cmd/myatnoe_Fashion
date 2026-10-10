<?php
require 'db.php';
if (($_SESSION['role'] ?? '') !== 'admin') { header('Location: login.php'); exit; }

$msg = ''; $err = '';

function make_slug($name) {
  $s = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-');
  return $s !== '' ? $s : 'cat-' . substr(uniqid(), -6);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['delete'])) {
    $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([(int)$_POST['delete']]);
    header('Location: categories.php?msg=Category+deleted'); exit;
  }
  $name = trim($_POST['name'] ?? '');
  $id = (int)($_POST['id'] ?? 0);
  if ($name === '') $err = 'Category name is required.';
  else {
    $slug = make_slug($name);
    $chk = $pdo->prepare("SELECT id FROM categories WHERE (slug=? OR name=?) AND id<>?");
    $chk->execute([$slug, $name, $id]);
    if ($chk->fetch()) $err = 'This category already exists.';
    else {
      if ($id) $pdo->prepare("UPDATE categories SET name=?, slug=? WHERE id=?")->execute([$name, $slug, $id]);
      else     $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?,?)")->execute([$name, $slug]);
      header('Location: categories.php?msg=Category+saved'); exit;
    }
  }
}

$edit = null;
if (isset($_GET['edit'])) {
  $st = $pdo->prepare("SELECT * FROM categories WHERE id=?"); $st->execute([(int)$_GET['edit']]);
  $edit = $st->fetch();
}
$cats = $pdo->query("SELECT c.*, COUNT(p.id) AS total FROM categories c LEFT JOIN products p ON p.category_id=c.id GROUP BY c.id ORDER BY c.id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Categories – Admin</title>
<link rel="stylesheet" href="admin.css">
</head><body>
<div class="adm">
  <?php include 'side.php'; ?>
  <main class="main">
    <h2>Categories</h2>
    <?php if (isset($_GET['msg'])): ?><p class="okmsg"><?= htmlspecialchars($_GET['msg']) ?></p><?php endif; ?>
    <?php if ($err): ?><p class="errmsg"><?= $err ?></p><?php endif; ?>

    <div class="two" style="grid-template-columns:1fr 340px">
      <div class="box">
        <table class="tb">
          <tr><th>#</th><th>Name</th><th>Slug</th><th>Products</th><th></th></tr>
          <?php foreach ($cats as $c): ?>
          <tr>
            <td><?= $c['id'] ?></td>
            <td><b><?= htmlspecialchars($c['name']) ?></b></td>
            <td><?= htmlspecialchars($c['slug']) ?></td>
            <td><?= $c['total'] ?></td>
            <td class="acts">
              <a class="ed" href="categories.php?edit=<?= $c['id'] ?>">Edit</a>
              <form method="post" onsubmit="return confirm('Delete this category? Its products will stay but have no category.')">
                <button class="dl" name="delete" value="<?= $c['id'] ?>">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>

      <div class="box pf">
        <h3><?= $edit ? 'Edit Category' : 'Add Category' ?></h3>
        <form method="post">
          <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
          <label>Category Name</label>
          <input name="name" value="<?= htmlspecialchars($edit['name'] ?? '') ?>" placeholder="e.g. Kids" required>
          <button class="sv big"><?= $edit ? 'Update' : 'Add Category' ?></button>
          <?php if ($edit): ?><a class="ed" href="categories.php" style="display:block;text-align:center;margin-top:10px">Cancel</a><?php endif; ?>
        </form>
      </div>
    </div>
  </main>
</div>
</body></html>