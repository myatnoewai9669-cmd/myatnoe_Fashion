

<?php $cur = basename($_SERVER['PHP_SELF']); ?>
<aside class="side">
  <a class="brand" href="index.php">Myat Noe Fashion</a>
  <a class="<?= $cur=='index.php'?'on':'' ?>" href="index.php">▦ Dashboard</a>
  <a class="<?= in_array($cur,['products.php','product_form.php'])?'on':'' ?>" href="products.php">👕 Products</a>
<a class="<?= in_array($cur,['shop.php','product_form.php'])?'on':'' ?>" href="shop.php">🛍️shop</a>
  <a class="<?= $cur=='categories.php'?'on':'' ?>" href="categories.php"> 📂Categories</a>
  <a class="<?= $cur=='orders.php'?'on':'' ?>" href="orders.php">🛒 Orders</a>
  <a class="<?= $cur=='payments.php'?'on':'' ?>" href="payments.php">💳 Payments</a>
<a class="<?= $cur=='customers.php'?'on':'' ?>" href="customers.php">👥 Customers</a>

  <a class="<?= $cur=='reviews.php'?'on':'' ?>" href="reviews.php">⭐ Reviews</a>
  <a href="index.php">📈 Sales Analytics</a>
  <a href="index.php">🌐 View Store</a>
  <a href="index.php"      href="admin.index.php"
  <a href="logout.php">⎋ Logout</a>
</aside>