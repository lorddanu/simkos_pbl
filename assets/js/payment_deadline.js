document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-payment-deadline]').forEach((timer) => {
    const deadline = Number(timer.dataset.deadlineMs);
    const serverNow = Number(timer.dataset.serverNowMs);
    const startedAt = performance.now();
    const counter = timer.querySelector('[data-countdown]');
    const message = timer.querySelector('[data-deadline-message]');
    let finishing = false;

    const closePaymentActions = () => {
      document.querySelectorAll('[data-payment-action]').forEach((action) => {
        if (action instanceof HTMLAnchorElement) {
          action.hidden = true;
        } else {
          action.disabled = true;
          action.textContent = 'Waktu pembayaran habis';
        }
      });
      timer.classList.add('is-expired');
      if (message) message.hidden = false;
    };

    const expireOnServer = () => {
      if (finishing) return;
      finishing = true;
      closePaymentActions();
      fetch(timer.dataset.expireUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
        body: new URLSearchParams({
          aksi: 'expire_pengajuan',
          id_pengajuan: timer.dataset.applicationId,
          csrf: timer.dataset.csrf,
        }),
      })
        .then((response) => response.json())
        .then((result) => {
          if (result.ok && result.expired) {
            window.location.replace(timer.dataset.notificationUrl);
            return;
          }
          finishing = false;
          window.setTimeout(expireOnServer, 1500);
        })
        .catch(() => {
          finishing = false;
          window.setTimeout(expireOnServer, 3000);
        });
    };

    let interval;
    const update = () => {
      const remaining = Math.max(0, deadline - serverNow - (performance.now() - startedAt));
      const totalSeconds = Math.ceil(remaining / 1000);
      const hours = Math.floor(totalSeconds / 3600);
      const minutes = Math.floor((totalSeconds % 3600) / 60);
      const seconds = totalSeconds % 60;
      const hoursLabel = String(hours).padStart(2, '0');
      const minutesLabel = String(minutes).padStart(2, '0');
      const secondsLabel = String(seconds).padStart(2, '0');
      if (counter) counter.textContent = `${hoursLabel}:${minutesLabel}:${secondsLabel}`;
      if (totalSeconds === 0) {
        window.clearInterval(interval);
        expireOnServer();
      }
    };

    interval = window.setInterval(update, 1000);
    update();
  });
});