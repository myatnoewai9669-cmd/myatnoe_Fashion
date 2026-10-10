<?php require 'db.php'; include 'header.php'; ?>
<section class="auth">
  <div class="box" style="text-align:center">
    <h2> Order Placed!</h2>
    <p>Your order number is</p>
    <p style="font-size:22px;font-weight:800;color:var(--pink)"><?= htmlspecialchars($_GET['no'] ?? '') ?></p>
    <a class="btn full" href="index.php">Continue Shopping</a>
  </div>
</section>
<?php include 'footer.php'; ?>