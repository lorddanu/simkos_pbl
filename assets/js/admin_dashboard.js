
    document.addEventListener('DOMContentLoaded', () => {
        // Navigasi dashboard
        document.querySelectorAll('.nav-link').forEach(button => {
            button.addEventListener('click', () => {
                const pageId = button.dataset.page;

                // Tampilkan halaman yang dipilih
                document.querySelectorAll('.page-section').forEach(section => {
                    section.classList.toggle(
                        'active',
                        section.id === pageId
                    );
                });

                // Aktifkan tombol navigasi
                document.querySelectorAll('.nav-link').forEach(nav => {
                    nav.classList.toggle('active', nav === button);
                });

                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        });

        // Unduh laporan pembayaran dalam format CSV
        const downloadReport = document.getElementById('downloadReport');

        if (downloadReport) {
            downloadReport.addEventListener('click', () => {
                const report = document.querySelector(
                    '#laporan-pembayaran .payment-report-table'
                );

                if (!report) {
                    alert('Data laporan pembayaran tidak ditemukan.');
                    return;
                }

                const rows = [
                    [
                        'Nama Penghuni',
                        'Kamar',
                        'Status Pembayaran',
                        'Uang Masuk'
                    ]
                ];

                report.querySelectorAll('tbody tr').forEach(row => {
                    const cells = Array.from(row.querySelectorAll('td'));
                    if (cells.length !== 4) return;
                    if (cells[0].classList.contains('payment-report-empty')) return;

                    rows.push(cells.map(cell => cell.textContent.trim()));
                });

                const totalRow = report.querySelector('tfoot tr');
                if (totalRow) {
                    const totalCells = Array.from(totalRow.querySelectorAll('td'));
                    rows.push([
                        'Total Pendapatan',
                        '',
                        '',
                        totalCells[1]?.textContent.trim() ?? ''
                    ]);
                }

                // Ubah data menjadi format CSV
                const csv = rows.map(row =>
                    row.map(value =>
                        `"${String(value).replace(/"/g, '""')}"`
                    ).join(',')
                ).join('\r\n');

                // Siapkan file CSV
                const blob = new Blob(
                    ['\uFEFF' + csv],
                    { type: 'text/csv;charset=utf-8;' }
                );

                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');

                link.href = url;
                link.download = 'laporan-pembayaran-simkos.csv';

                document.body.appendChild(link);
                link.click();
                link.remove();

                URL.revokeObjectURL(url);
            });
        }

        const monthPicker = document.getElementById('monthPicker');
        const monthPickerToggle = document.getElementById('monthPickerToggle');

        if (monthPicker && monthPickerToggle) {
            const monthHeading = document.getElementById('pickerMonthHeading');
            const yearHeading = document.getElementById('pickerYearHeading');
            const monthGrid = document.getElementById('pickerMonthGrid');
            const yearGrid = document.getElementById('pickerYearGrid');
            let selectedMonth = Number(monthPicker.dataset.selectedMonth);
            let selectedYear = Number(monthPicker.dataset.selectedYear);

            const submitSelectedMonth = () => {
                const month = String(selectedMonth).padStart(2, '0');
                window.location.href =
                    '?bulan=' + selectedYear + '-' + month +
                    '#laporan-pembayaran';
            };

            monthPickerToggle.addEventListener('click', () => {
                monthPicker.hidden = !monthPicker.hidden;
                monthPickerToggle.setAttribute(
                    'aria-expanded',
                    String(!monthPicker.hidden)
                );
            });

            monthHeading.addEventListener('click', () => {
                yearGrid.hidden = true;
                monthGrid.hidden = false;
            });

            yearHeading.addEventListener('click', () => {
                monthGrid.hidden = true;
                yearGrid.hidden = false;
            });

            monthGrid.querySelectorAll('[data-month]').forEach(button => {
                button.addEventListener('click', () => {
                    selectedMonth = Number(button.dataset.month);
                    submitSelectedMonth();
                });
            });

            yearGrid.querySelectorAll('[data-year]').forEach(button => {
                button.addEventListener('click', () => {
                    selectedYear = Number(button.dataset.year);
                    monthGrid.hidden = false;
                    yearGrid.hidden = true;
                    yearHeading.textContent = String(selectedYear);
                    yearGrid.querySelectorAll('[data-year]').forEach(option => {
                        option.classList.toggle(
                            'selected',
                            Number(option.dataset.year) === selectedYear
                        );
                    });
                });
            });

            document.getElementById('pickerPrevious').addEventListener('click', () => {
                selectedYear--;
                yearHeading.textContent = String(selectedYear);
            });

            document.getElementById('pickerNext').addEventListener('click', () => {
                selectedYear++;
                yearHeading.textContent = String(selectedYear);
            });

            document.addEventListener('click', event => {
                if (!event.target.closest('.month-picker-container')) {
                    monthPicker.hidden = true;
                    monthPickerToggle.setAttribute('aria-expanded', 'false');
                }
            });

            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && !monthPicker.hidden) {
                    monthPicker.hidden = true;
                    monthPickerToggle.setAttribute('aria-expanded', 'false');
                    monthPickerToggle.focus();
                }
            });
        }

        // Tab pembayaran
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                const tabId = button.dataset.tab;

                document.querySelectorAll('.payment-tab').forEach(tab => {
                    tab.hidden = tab.id !== tabId;
                });

                document.querySelectorAll('.tab-button').forEach(tabButton => {
                    tabButton.classList.toggle('active', tabButton === button);
                });
            });
        });

        // Buka tab sesuai hash URL setelah halaman dimuat
        if (window.location.hash === '#laporan-pembayaran') {
            const tombolLaporan = document.querySelector(
                '.tab-button[data-tab="laporan-pembayaran"]'
            );

            if (tombolLaporan) {
                tombolLaporan.click();
            }
        }

        // Menu profil dan logout
        const profileButton = document.getElementById('profileButton');
        const profileDropdown = document.getElementById('profileDropdown');

        if (profileButton && profileDropdown) {
            profileButton.addEventListener('click', event => {
                event.stopPropagation();

                const isOpening = profileDropdown.hidden;
                profileDropdown.hidden = !isOpening;

                profileButton.setAttribute('aria-expanded', String(isOpening));
            });

            document.addEventListener('click', event => {
                if (!event.target.closest('.profile-menu')) {
                    profileDropdown.hidden = true;
                    profileButton.setAttribute('aria-expanded', 'false');
                }
            });
        }
    });

    // Tab Kamar dan Penghuni
    const dataTabs = document.querySelectorAll("[data-data-tab]");
    const dataContents = document.querySelectorAll(".data-tab-content");

    dataTabs.forEach((button) => {
        button.addEventListener("click", () => {
            const target = button.dataset.dataTab;

            dataTabs.forEach((tab) => {
                tab.classList.toggle("active", tab === button);
            });

            dataContents.forEach((content) => {
                content.hidden = content.id !== `tab-${target}`;
            });
        });
    });

    // tambah kamar dan ubah data kamar
    const btnTambahKamar = document.getElementById('btnTambahKamar');

    if (btnTambahKamar) {
        btnTambahKamar.addEventListener('click', () => {
            document.getElementById('formTambahKamar').reset();
            tampilkanHalamanKamar('tambah-kamar');
        });
    }

    // Fungsi menampilkan halaman kamar
    function tampilkanHalamanKamar(idHalaman) {
        document.querySelectorAll('.page-section').forEach(section => {
            section.classList.toggle('active', section.id === idHalaman);
        });

        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.toggle('active', link.dataset.page === 'data');
        });

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Tombol kembali ke daftar kamar
    document.querySelectorAll('[data-kembali-kamar]').forEach(button => {
        button.addEventListener('click', () => {
            tampilkanHalamanKamar('data');

            document.querySelectorAll('[data-data-tab]').forEach(tab => {
                tab.classList.toggle('active', tab.dataset.dataTab === 'kamar');
            });

            document.querySelectorAll('.data-tab-content').forEach(content => {
                content.hidden = content.id !== 'tab-kamar';
            });
        });
    });

    // Tombol ubah kamar
    document.querySelectorAll('.kamar-card .btn-kamar').forEach(button => {
        if (button.textContent.trim() !== 'Ubah') return;

        button.addEventListener('click', () => {
            const kartu = button.closest('.kamar-card');

            const nama = kartu.querySelector('h3').textContent.trim();
            const hargaText = kartu.querySelector('p').textContent;
            const harga = hargaText.replace(/[^\d]/g, '');

            const formUbah = document.getElementById('formUbahKamar');

            formUbah.reset();

            document.getElementById('ubahId').value = nama;
            document.getElementById('ubahNomor').value = nama;
            document.getElementById('ubahHarga').value = harga;
            document.getElementById('ubahDeskripsi').value = '';

            tampilkanHalamanKamar('ubah-kamar');
        });
    });

// ======================================
// NAVIGASI SECTION DI HALAMAN KAMAR
// ======================================

function tampilkanSectionKamar(idSection) {
    document.querySelectorAll('.page-section').forEach(section => {
        section.classList.toggle('active', section.id === idSection);
    });

    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}


// ======================================
// UBAH STATUS KAMAR
// ======================================

document.querySelectorAll('[data-ubah-status]').forEach(button => {
    button.addEventListener('click', () => {
        const kartu = button.closest('.kamar-card');

        const id = kartu.dataset.id;
        const nomor = kartu.querySelector('h3').textContent.trim();
        const status = kartu.querySelector('.kamar-status').textContent.trim();

        document.getElementById('statusIdKamar').value = id;
        document.getElementById('statusNomorKamar').textContent = nomor;
        document.getElementById('statusSaatIni').textContent = status;

        const radioStatus = document.querySelectorAll(
            '#formUbahStatus input[name="status"]'
        );

        radioStatus.forEach(radio => {
            radio.checked = radio.value === status;
        });

        document.getElementById('catatanStatus').value = '';

        perbaruiPilihanStatus();

        tampilkanSectionKamar('ubah-status-kamar');
    });
});


// Tampilan pilihan status yang aktif
function perbaruiPilihanStatus() {
    document.querySelectorAll('.status-option').forEach(option => {
        const radio = option.querySelector('input');

        option.classList.toggle('selected', radio.checked);
    });
}

document.querySelectorAll(
    '#formUbahStatus input[name="status"]'
).forEach(radio => {
    radio.addEventListener('change', perbaruiPilihanStatus);
});


// ======================================
// UBAH DATA PENGHUNI
// ======================================

document.querySelectorAll('[data-ubah-penghuni]').forEach(button => {
    button.addEventListener('click', () => {
        const kartu = button.closest('.penghuni-card');

        const nama = kartu.querySelector('h3').textContent.trim();

        document.getElementById('penghuniNama').value = nama;

        // Data sementara untuk tampilan
        document.getElementById('penghuniId').value = 1;
        document.getElementById('penghuniEmail').value =
            'andi.wijaya@email.com';

        document.getElementById('penghuniTelepon').value =
            '0813-xxxx-xxxx';

        document.getElementById('penghuniKamar').value = '5';

        tampilkanSectionKamar('ubah-data-penghuni');
    });
});


// ======================================
// TOMBOL BATAL / KEMBALI
// ======================================

document.querySelectorAll('[data-kembali-data]').forEach(button => {
    button.addEventListener('click', () => {
        tampilkanSectionKamar('data');

        // Kembali ke tab yang sesuai
        document.querySelectorAll('[data-data-tab]').forEach(tab => {
            tab.classList.toggle(
                'active',
                tab.dataset.dataTab === 'kamar'
            );
        });

        document.querySelectorAll('.data-tab-content').forEach(content => {
            content.hidden = content.id !== 'tab-kamar';
        });
    });
});

// Menampilkan halaman riwayat
function tampilkanRiwayat(id) {
    document.querySelectorAll('.page-section').forEach(section => {
        section.classList.toggle('active', section.id === id);
    });

    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Tombol Riwayat Huni Kamar
document.querySelectorAll('[data-riwayat-kamar]').forEach(button => {
    button.addEventListener('click', () => {
        const kartu = button.closest('.kamar-card');
        const nomorKamar = kartu.querySelector('h3').textContent.trim();

        document.querySelector('#riwayat-huni-kamar h2').textContent =
            'Riwayat Huni ' + nomorKamar;

        tampilkanRiwayat('riwayat-huni-kamar');
    });
});

// Tombol Riwayat Penghuni
document.querySelectorAll('[data-riwayat-penghuni]').forEach(button => {
    button.addEventListener('click', () => {
        tampilkanRiwayat('riwayat-penghuni');
    });
});

// Tombol kembali dari halaman riwayat
document.querySelectorAll('[data-kembali-riwayat]').forEach(button => {
    button.addEventListener('click', () => {
        tampilkanRiwayat('data');

        const tabTujuan = button.dataset.kembaliRiwayat;

        document.querySelectorAll('[data-data-tab]').forEach(tab => {
            tab.classList.toggle(
                'active',
                tab.dataset.dataTab === tabTujuan
            );
        });

        document.querySelectorAll('.data-tab-content').forEach(content => {
            content.hidden = content.id !== 'tab-' + tabTujuan;
        });
    });
});

// Membuka halaman ubah data kamar
document.querySelectorAll('[data-ubah-kamar]').forEach(button => {
    button.addEventListener('click', () => {

        const kartu = button.closest('.kamar-card');

        const id = kartu.dataset.id;
        const nomor = kartu.querySelector('h3').textContent.trim();

        document.getElementById('ubahId').value = id;
        document.getElementById('ubahNomor').value = nomor;
        document.getElementById('ubahHarga').value = 650000;

        document.getElementById('ubahDeskripsi').value =
            'Dekat kampus, 10 menit jalan kaki. Parkir motor tersedia.';

        document.getElementById('ubahFoto').value = '';

        tampilkanSectionKamar('ubah-kamar');
    });
});

// Membuka popup hapus kamar
document.getElementById('btnHapusKamar').addEventListener('click', () => {
    document.getElementById('popupHapusKamar').hidden = false;
});

// Membatalkan hapus
document.getElementById('btnBatalHapus').addEventListener('click', () => {
    document.getElementById('popupHapusKamar').hidden = true;
});

// Konfirmasi hapus
document.getElementById('btnKonfirmasiHapus').addEventListener('click', () => {
    const idKamar = document.getElementById('ubahId').value;

    alert('Kamar dengan ID ' + idKamar + ' siap dihapus.');

    document.getElementById('popupHapusKamar').hidden = true;
});

// Balas admin
function toggleBalasan(button) {
    const laporanCard = button.closest('.laporan-card');
    const formBalasan = laporanCard.querySelector('.form-balasan');

    formBalasan.hidden = !formBalasan.hidden;
}