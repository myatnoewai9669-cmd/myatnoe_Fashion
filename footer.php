<footer class="ft">
  <div><b>Myat Noe Fashion</b><br>Express your unique style.</div>
  <div>© 2026 Myat Noe Fashion. All rights reserved.</div>
</footer>
<script>
function wl(el, id){
  fetch('wishlist.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'ajax=1&id=' + id
  }).then(r => r.json()).then(d => {
    if (d.login) { location.href = 'login.php?next=wishlist.php'; return; }
    el.classList.toggle('on', d.on);
    el.textContent = d.on ? '♥' : '♡';
    const b = document.getElementById('wlc');
    if (b) { b.textContent = d.count; b.style.display = d.count ? '' : 'none'; }
  });
}
</script>
</body>
</html>