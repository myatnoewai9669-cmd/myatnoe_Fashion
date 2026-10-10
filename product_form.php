<?php
require 'db.php';
if (($_SESSION['role'] ?? '') !== 'admin') { header('Location: login.php'); exit; }

$id = (int)($_GET['id'] ?? 0);
$p = ['name'=>'','category_id'=>'','description'=>'','price'=>'','old_price'=>'','stock'=>10,'main_image'=>'','is_new'=>0,'is_best_seller'=>0];
$colors = []; $sizes = [];
if ($id) {
  $st = $pdo->prepare("SELECT * FROM products WHERE id=?"); $st->execute([$id]);
  $p = $st->fetch() ?: $p;
  $st = $pdo->prepare("SELECT * FROM product_colors WHERE product_id=?"); $st->execute([$id]); $colors = $st->fetchAll();
  $st = $pdo->prepare("SELECT size FROM product_sizes WHERE product_id=?"); $st->execute([$id]); $sizes = $st->fetchAll(PDO::FETCH_COLUMN);
}
$cats = $pdo->query("SELECT * FROM categories")->fetchAll();
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name  = trim($_POST['name']);
  $desc  = trim($_POST['description']);
  $price = (int)$_POST['price'];
  $old   = (int)$_POST['old_price'];
  $stock = (int)$_POST['stock'];
  $cat   = (int)$_POST['category_id'] ?: null;
  $new   = isset($_POST['is_new']) ? 1 : 0;
  $best  = isset($_POST['is_best_seller']) ? 1 : 0;
  $disc  = ($old > $price && $price > 0) ? round(($old - $price) / $old * 100) : 0;
  $oldDb = $old > $price ? $old : null;
  $image = $p['main_image'];

  $f = $_FILES['image'] ?? null;
  if ($f && $f['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
      $err = 'Image is too big for the server. Use a smaller image, or raise upload_max_filesize in php.ini.';
    } elseif ($f['error'] !== UPLOAD_ERR_OK) {
      $err = 'Upload failed (error code ' . $f['error'] . ').';
    } else {
      $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
      if (!in_array($ext, ['jpg','jpeg','png','webp'])) $err = 'Image must be JPG, PNG or WEBP.';
      elseif ($f['size'] > 8 * 1024 * 1024) $err = 'Image must be under 8MB.';
      else {
        $dir = ROOT_DIR . '/uploads';
        if (!is_dir($dir)) @mkdir($dir, 0777, true);
        $fn = 'p_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($f['tmp_name'], $dir . '/' . $fn)) {
          if ($image && is_file(ROOT_DIR . '/' . $image)) @unlink(ROOT_DIR . '/' . $image);
          $image = 'uploads/' . $fn;
        } else $err = 'Could not save image. Check that the uploads folder exists.';
      }
    }
  }
  if (!$err && (!$name || $price <= 0)) $err = 'Name and price are required.';

  if (!$err) {
    $pdo->beginTransaction();
    if ($id) {
      $pdo->prepare("UPDATE products SET category_id=?,name=?,description=?,price=?,old_price=?,discount_percent=?,stock=?,main_image=?,is_new=?,is_best_seller=? WHERE id=?")
          ->execute([$cat,$name,$desc,$price,$oldDb,$disc,$stock,$image,$new,$best,$id]);
    } else {
      $pdo->prepare("INSERT INTO products (category_id,name,description,price,old_price,discount_percent,stock,main_image,is_new,is_best_seller) VALUES (?,?,?,?,?,?,?,?,?,?)")
          ->execute([$cat,$name,$desc,$price,$oldDb,$disc,$stock,$image,$new,$best]);
      $id = $pdo->lastInsertId();
    }
    $pdo->prepare("DELETE FROM product_colors WHERE product_id=?")->execute([$id]);
    $ic = $pdo->prepare("INSERT INTO product_colors (product_id,color_name,hex_code) VALUES (?,?,?)");
    foreach (($_POST['color_name'] ?? []) as $i => $cn) {
      $cn = trim($cn); $hex = $_POST['color_hex'][$i] ?? '#000000';
      if ($cn !== '' && preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) $ic->execute([$id, $cn, $hex]);
    }
    $pdo->prepare("DELETE FROM product_sizes WHERE product_id=?")->execute([$id]);
    $is = $pdo->prepare("INSERT INTO product_sizes (product_id,size,stock) VALUES (?,?,?)");
    foreach (($_POST['sizes'] ?? []) as $sz) if (in_array($sz, ['XS','S','M','L','XL','XXL'])) $is->execute([$id, $sz, $stock]);
    $pdo->commit();
    header('Location: products.php?msg=saved'); exit;
  }
  // keep typed values on error
  $p = array_merge($p, ['name'=>$name,'description'=>$desc,'price'=>$price,'old_price'=>$old,'stock'=>$stock,'category_id'=>$cat,'is_new'=>$new,'is_best_seller'=>$best]);
}
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $id ? 'Edit' : 'Add' ?> Product - Admin</title>
<link rel="stylesheet" href="admin.css">
</head><body>
<div class="adm">
  <?php include 'side.php'; ?>
  <main class="main">
    <div class="top"><h2><?= $id ? 'Edit Product' : 'Add Product' ?></h2><a class="ed" href="products.php"> Back</a></div>
    <?php if ($err): ?><p class="errmsg"><?= htmlspecialchars($err) ?></p><?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="box pf">
      <label>Product Name</label>
      <input name="name" value="<?= htmlspecialchars($p['name']) ?>" required>

      <label>Category</label>
      <select name="category_id">
        <option value="0"> None </option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $p['category_id']==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <div class="g3">
        <div><label>Price (Rp)</label><input type="number" name="price" value="<?= $p['price'] ?>" required></div>
        <div><label>Old Price (optional)</label><input type="number" name="old_price" value="<?= $p['old_price'] ?>"></div>
        <div><label>Stock</label><input type="number" name="stock" value="<?= $p['stock'] ?>"></div>
      </div>

      <label>Description</label>
      <textarea name="description" rows="4"><?= htmlspecialchars($p['description']) ?></textarea>

      <label>Product Image (JPG / PNG / WEBP, max 8MB)</label>
      <?php if ($p['main_image']): ?><img class="prev" src="<?= BASE_URL ?>/<?= htmlspecialchars($p['main_image']) ?>" alt=""><?php endif; ?>
      <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">

      <label>Sizes</label>
      <div class="chk">
        <?php foreach (['XS','S','M','L','XL','XXL'] as $s): ?>
          <label><input type="checkbox" name="sizes[]" value="<?= $s ?>" <?= in_array($s,$sizes)?'checked':'' ?>> <?= $s ?></label>
        <?php endforeach; ?>
      </div>

      <label>Colors</label>
      <div id="colors">
        <?php foreach ($colors ?: [['color_name'=>'','hex_code'=>'#e8336d']] as $c): ?>
          <div class="crow"><input name="color_name[]" placeholder="Color name (e.g. Pink)" value="<?= htmlspecialchars($c['color_name']) ?>"><input type="color" name="color_hex[]" value="<?= $c['hex_code'] ?>"><button type="button" class="dl" onclick="this.parentNode.remove()"></button></div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="ed" onclick="addColor()">+ Add color</button>

      <div class="chk" style="margin-top:16px">
        <label><input type="checkbox" name="is_new" <?= $p['is_new']?'checked':'' ?>> New Arrival</label>
        <label><input type="checkbox" name="is_best_seller" <?= $p['is_best_seller']?'checked':'' ?>> Best Seller</label>
      </div>

      <button class="sv big">Save Product</button>
    </form>
  </main>
</div>
<script>
function addColor(){
  const d=document.createElement('div');d.className='crow';
  d.innerHTML='<input name="color_name[]" placeholder="Color name"><input type="color" name="color_hex[]" value="#e8336d"><button type="button" class="dl" onclick="this.parentNode.remove()"></button>';
  document.getElementById('colors').appendChild(d);
}
</script>
</body></html>