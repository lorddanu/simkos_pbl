(() => {
  const navigation = performance.getEntriesByType('navigation')[0];
  if (!navigation || navigation.type !== 'reload') return;

  const config = document.getElementById('demo-reset-config');
  if (!config) return;

  fetch(config.dataset.endpoint, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
    body: new URLSearchParams({ csrf: config.dataset.csrf }),
  })
    .then((response) => {
      if (!response.ok) throw new Error('Reset simulasi gagal.');
      return response.json();
    })
    .then((result) => {
      if (!result.ok) throw new Error('Reset simulasi gagal.');
      window.location.replace(config.dataset.home);
    })
    .catch(() => window.alert('Data simulasi belum berhasil direset. Muat ulang kembali atau periksa koneksi database.'));
})();
