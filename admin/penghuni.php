
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
        <h1>Data Penghuni</h1>

        <p>Halaman untuk melihat dan mengelola data penghuni kos.</p>

        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Nama Penghuni</th>
                    <th>Kamar</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td colspan="4">
                        Data penghuni belum dihubungkan ke database.
                    </td>
                </tr>
            </tbody>
        </table>
    </section>
</main>

<?php include '../includes/footer.php'; ?>
