<h2 class="t">Saran &amp; Keluhan</h2>
<p class="s">Masukan Anda akan dikirim kepada admin atau pemilik kos.</p>
<?php if ($error): ?><p class="err" role="alert"><?= h($error) ?></p><?php endif; ?>
<form method="post" class="complaint-form">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
  <input type="hidden" name="aksi" value="kirim_keluhan">
  <label for="isi_keluhan">Keluhan atau masukan</label>
  <textarea id="isi_keluhan" name="isi_keluhan" placeholder="Tuliskan masukan Anda..."></textarea>
  <button class="btn" type="submit">Kirim Masukan</button>
</form>
