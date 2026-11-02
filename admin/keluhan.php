
<?php
session_start();

if (!isset($_SESSION['id_akun'])) {
    header('Location: ../auth/login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak.');
}

$nama = $_SESSION['nama'] ?? 'Admin SIMKOS';
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<main class="admin-main">
    <section class="page-section">
        <h1>Kelola Keluhan</h1>

        <p>
            Halaman untuk melihat keluhan yang disampaikan penghuni kos.
        </p>

        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Penghuni</th>
                    <th>Keluhan</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td colspan="6">
                        Data keluhan belum dihubungkan ke database.
                    </td>
                </tr>
            </tbody>
        </table>
    </section>
</main>

<?php include '../includes/footer.php'; ?>
