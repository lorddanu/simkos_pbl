<div class="upload-heading">
  <h2>Ajukan Sewa Kamar</h2>
  <p>Kamar <?= h($kamarDipilih['nomor_kamar']) ?></p>
</div>
<?php if ($error): ?><p class="err" role="alert"><?= h($error) ?></p><?php endif; ?>
<form method="post" action="index.php?halaman=ajukan&amp;id=<?= (int) $kamarDipilih['id_kamar'] ?>" class="upload-form" id="form-sewa">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
  <input type="hidden" name="aksi" value="kirim_pengajuan">
  <input type="hidden" name="id_kamar" value="<?= (int) $kamarDipilih['id_kamar'] ?>">
  <section class="payment-card">
    <strong class="monthly-price">Rp <?= h(number_format((float) $kamarDipilih['harga_bulanan'], 0, ',', '.')) ?><small>/bulan</small></strong>
    <div class="date-row">
      <label>Tanggal Mulai Sewa
        <span class="date-picker" data-date-picker data-date-min="<?= date('Y-m-d') ?>">
          <input class="date-picker-display" type="text" placeholder="dd/mm/yyyy" value="" readonly required aria-haspopup="dialog" aria-expanded="false" aria-label="Pilih tanggal mulai sewa">
          <input type="hidden" id="tanggal_mulai" name="tanggal_mulai" value="<?= h($nilaiMulai) ?>">
          <button class="date-picker-trigger" type="button" aria-label="Buka kalender tanggal mulai">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18"></path></svg>
          </button>
          <div class="calendar-popover" data-calendar-popover role="dialog" aria-label="Pilih tanggal" hidden>
            <div class="calendar-heading">
              <select class="calendar-month" aria-label="Pilih bulan"></select>
              <select class="calendar-year" aria-label="Pilih tahun"></select>
              <button type="button" class="calendar-nav calendar-prev" aria-label="Bulan sebelumnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6-6 6 6 6"/></svg></button>
              <button type="button" class="calendar-nav calendar-next" aria-label="Bulan berikutnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m10 6 6 6-6 6"/></svg></button>
            </div>
            <div class="calendar-weekdays" aria-hidden="true"><span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span></div>
            <div class="calendar-days" data-calendar-days></div>
          </div>
        </span>
      </label>
      <label>Tanggal Akhir Sewa
        <span class="date-picker" data-date-picker data-date-min="<?= date('Y-m-d') ?>">
          <input class="date-picker-display" type="text" placeholder="dd/mm/yyyy" value="" readonly required aria-haspopup="dialog" aria-expanded="false" aria-label="Pilih tanggal akhir sewa">
          <input type="hidden" id="tanggal_selesai" name="tanggal_selesai" value="<?= h($nilaiSelesai) ?>">
          <button class="date-picker-trigger" type="button" aria-label="Buka kalender tanggal akhir">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18"></path></svg>
          </button>
          <div class="calendar-popover" data-calendar-popover role="dialog" aria-label="Pilih tanggal" hidden>
            <div class="calendar-heading">
              <select class="calendar-month" aria-label="Pilih bulan"></select>
              <select class="calendar-year" aria-label="Pilih tahun"></select>
              <button type="button" class="calendar-nav calendar-prev" aria-label="Bulan sebelumnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6-6 6 6 6"/></svg></button>
              <button type="button" class="calendar-nav calendar-next" aria-label="Bulan berikutnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m10 6 6 6-6 6"/></svg></button>
            </div>
            <div class="calendar-weekdays" aria-hidden="true"><span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span></div>
            <div class="calendar-days" data-calendar-days></div>
          </div>
        </span>
      </label>
    </div>
    <small class="hint">Pilih tanggal penyewaan Anda.</small>
  </section>
  <section class="duration-card">
    <label for="bulan">Jumlah Bulan yang Ingin Disewa</label>
    <div class="stepper"><button type="button" id="kurangi-bulan" aria-label="Kurangi bulan">-</button><output id="bulan-label"><?= (int) $jumlahBulan ?></output><button type="button" id="tambah-bulan" aria-label="Tambah bulan">+</button></div>
    <input type="hidden" id="bulan" name="bulan" value="<?= (int) $jumlahBulan ?>">
  </section>
  <label class="field-label" for="catatan">Catatan (opsional)</label>
  <textarea class="note-input" id="catatan" name="catatan" placeholder="Transfer dari rekening an. orang tua, dll."></textarea>
  <div class="bottom-action"><button class="btn" type="submit">Kirim untuk Diverifikasi</button></div>
</form>
<script>
(() => {
  const form = document.getElementById('form-sewa');
  const startInput = document.getElementById('tanggal_mulai');
  const endInput = document.getElementById('tanggal_selesai');
  const monthInput = document.getElementById('bulan');
  const monthLabel = document.getElementById('bulan-label');
  const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  const today = new Date().toISOString().slice(0, 10);
  let baseMonths = Number(monthInput.value) || 1;
  let endBaseline = endInput.value;

  const parseIso = (value) => value ? value.split('-').map(Number) : null;
  const formatDate = (value) => {
    const parts = parseIso(value);
    return parts ? `${String(parts[2]).padStart(2, '0')}/${String(parts[1]).padStart(2, '0')}/${parts[0]}` : '';
  };
  const addMonths = (value, amount) => {
    const parts = parseIso(value);
    if (!parts) return '';
    const monthIndex = parts[0] * 12 + parts[1] - 1 + amount;
    const year = Math.floor(monthIndex / 12);
    const month = ((monthIndex % 12) + 12) % 12;
    const lastDay = new Date(Date.UTC(year, month + 1, 0)).getUTCDate();
    return `${year}-${String(month + 1).padStart(2, '0')}-${String(Math.min(parts[2], lastDay)).padStart(2, '0')}`;
  };
  const addDays = (value, amount) => {
    const parts = parseIso(value);
    if (!parts) return today;
    const date = new Date(Date.UTC(parts[0], parts[1] - 1, parts[2] + amount));
    return `${date.getUTCFullYear()}-${String(date.getUTCMonth() + 1).padStart(2, '0')}-${String(date.getUTCDate()).padStart(2, '0')}`;
  };
  const syncDisplay = (hiddenInput) => {
    const picker = hiddenInput.closest('[data-date-picker]');
    picker.querySelector('.date-picker-display').value = formatDate(hiddenInput.value);
  };
  const refreshMin = () => {
    const startMin = startInput.closest('[data-date-picker]').dataset.dateMin || today;
    const endMin = startInput.value ? addDays(startInput.value, 1) : (endInput.closest('[data-date-picker]').dataset.dateMin || today);
    startInput.closest('[data-date-picker]').dataset.min = startMin;
    endInput.closest('[data-date-picker]').dataset.min = endMin;
    endInput.closest('[data-date-picker]').querySelector('.date-picker-display').setCustomValidity(
      endInput.value && startInput.value && endInput.value <= startInput.value ? 'Tanggal akhir harus setelah tanggal mulai.' : ''
    );
  };

  const pickers = [...form.querySelectorAll('[data-date-picker]')];
  pickers.forEach((picker) => {
    const hiddenInput = picker.querySelector('input[type="hidden"]');
    const display = picker.querySelector('.date-picker-display');
    const popover = picker.querySelector('[data-calendar-popover]');
    const monthSelect = picker.querySelector('.calendar-month');
    const yearSelect = picker.querySelector('.calendar-year');
    const daysGrid = picker.querySelector('[data-calendar-days]');
    let viewYear;
    let viewMonth;

    monthNames.forEach((name, index) => monthSelect.add(new Option(name, String(index))));
    const thisYear = new Date().getFullYear();
    for (let year = thisYear - 1; year <= thisYear + 12; year += 1) yearSelect.add(new Option(String(year), String(year)));

    const render = () => {
      monthSelect.value = String(viewMonth);
      yearSelect.value = String(viewYear);
      const firstWeekday = new Date(Date.UTC(viewYear, viewMonth, 1)).getUTCDay();
      const daysInMonth = new Date(Date.UTC(viewYear, viewMonth + 1, 0)).getUTCDate();
      const selected = hiddenInput.value;
      const min = picker.dataset.min || picker.dataset.dateMin || today;
      daysGrid.replaceChildren();
      for (let blank = 0; blank < firstWeekday; blank += 1) daysGrid.append(document.createElement('span'));
      for (let day = 1; day <= daysInMonth; day += 1) {
        const dateValue = `${viewYear}-${String(viewMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'calendar-day';
        button.textContent = String(day);
        button.disabled = dateValue < min;
        if (dateValue === selected) button.classList.add('is-selected');
        button.addEventListener('click', () => {
          hiddenInput.value = dateValue;
          hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
          closeCalendar(picker);
        });
        daysGrid.append(button);
      }
    };
    const setView = (year, month) => {
      const date = new Date(Date.UTC(year, month, 1));
      viewYear = date.getUTCFullYear();
      viewMonth = date.getUTCMonth();
      render();
    };
    const openCalendar = () => {
      const initial = parseIso(hiddenInput.value) || parseIso(today);
      setView(initial[0], initial[1] - 1);
      popover.hidden = false;
      display.setAttribute('aria-expanded', 'true');
      const rect = display.getBoundingClientRect();
      const width = Math.min(288, window.innerWidth - 24);
      const height = 316;
      const left = Math.max(12, Math.min(rect.left, window.innerWidth - width - 12));
      const top = rect.bottom + 4 + height > window.innerHeight ? Math.max(8, rect.top - height - 4) : rect.bottom + 4;
      popover.style.left = `${left}px`;
      popover.style.top = `${top}px`;
      render();
    };
    display.addEventListener('click', openCalendar);
    picker.querySelector('.date-picker-trigger').addEventListener('click', openCalendar);
    monthSelect.addEventListener('change', () => setView(viewYear, Number(monthSelect.value)));
    yearSelect.addEventListener('change', () => setView(Number(yearSelect.value), viewMonth));
    picker.querySelector('.calendar-prev').addEventListener('click', () => setView(viewYear, viewMonth - 1));
    picker.querySelector('.calendar-next').addEventListener('click', () => setView(viewYear, viewMonth + 1));
    syncDisplay(hiddenInput);
  });

  function closeCalendar(picker) {
    const popover = picker.querySelector('[data-calendar-popover]');
    popover.hidden = true;
    picker.querySelector('.date-picker-display').setAttribute('aria-expanded', 'false');
  }
  document.addEventListener('click', (event) => {
    pickers.forEach((picker) => {
      if (!picker.contains(event.target)) closeCalendar(picker);
    });
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') pickers.forEach(closeCalendar);
  });

  startInput.addEventListener('change', () => {
    syncDisplay(startInput);
    if (!startInput.value) return;
    endInput.value = addMonths(startInput.value, Number(monthInput.value) || 1);
    endBaseline = endInput.value;
    baseMonths = Number(monthInput.value) || 1;
    refreshMin();
    syncDisplay(endInput);
  });
  endInput.addEventListener('change', () => {
    syncDisplay(endInput);
    endBaseline = endInput.value;
    baseMonths = Number(monthInput.value) || 1;
    refreshMin();
  });
  const updateDuration = (delta) => {
    const current = Number(monthInput.value) || 1;
    const updated = Math.max(1, Math.min(12, current + delta));
    monthInput.value = updated;
    monthLabel.value = updated;
    if (endBaseline) {
      endInput.value = addMonths(endBaseline, updated - baseMonths);
      syncDisplay(endInput);
      refreshMin();
    }
  };
  document.getElementById('kurangi-bulan').addEventListener('click', () => updateDuration(-1));
  document.getElementById('tambah-bulan').addEventListener('click', () => updateDuration(1));
  form.addEventListener('submit', (event) => {
    refreshMin();
    if (!startInput.value || !endInput.value || endInput.value <= startInput.value) {
      event.preventDefault();
      (startInput.value ? endInput : startInput).closest('[data-date-picker]').querySelector('.date-picker-display').reportValidity();
    }
  });
  refreshMin();
})();
</script>
