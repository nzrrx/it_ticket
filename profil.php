<?php
session_start();
include 'includes/db.php';

/* PROTEKSI USER */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

//$user_id = (int) $_SESSION['user_id'];
$user_id = $_SESSION['user_id'];

$error   = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {

    $old_password     = trim($_POST['old_password'] ?? '');
    $new_password     = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // 1️⃣ Validasi field kosong
    if ($old_password === '' || $new_password === '' || $confirm_password === '') {
        $_SESSION['error'] = "Semua field password wajib diisi.";
        header("Location: profil.php");
        exit;
    }

    // 2️⃣ Validasi password baru & konfirmasi
    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = "Password baru dan konfirmasi password tidak cocok.";
        header("Location: profil.php");
        exit;
    }

    // 3️⃣ Ambil password lama dari DB
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    // 4️⃣ Verifikasi password lama
    if (!$result || !password_verify($old_password, $result['password'])) {
        $_SESSION['error'] = "Password lama salah.";
        header("Location: profil.php");
        exit;
    }

    // 5️⃣ Update password
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed, $_SESSION['user_id']);
    $stmt->execute();

    $_SESSION['success'] = "Password berhasil diperbarui.";
    $_SESSION['password_changed'] = true;

    header("Location: profil.php");
    exit;
}
if (isset($_SESSION['password_changed'])) {
    // Simpan flag dulu
    $forceLogout = true;

    // HAPUS SESSION SETELAH HTML (nanti)
}



/* AMBIL DATA USER */
$stmt = $conn->prepare("
    SELECT id, name, email, role, created_at
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Profil Saya | MICS IT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <!-- CSS (SAMA DENGAN DASHBOARD) -->
    <style>
        body {
            background-color: #f0f9ff;
            font-family: 'Segoe UI', sans-serif;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            background: linear-gradient(180deg, #0ea5e9, #0284c7);
            position: fixed;
            color: #fff;
        }

        .sidebar h4 {
            font-weight: 700;
        }

        .sidebar a {
            color: #e0f2fe;
            text-decoration: none;
            display: block;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 5px;
            transition: 0.3s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .content {
            margin-left: 260px;
            padding: 30px;
        }

        .card-stat {
            border: none;
            border-radius: 18px;
            box-shadow: 0 10px 25px rgba(14, 165, 233, .2);
        }

        .topbar {
            background-color: #ffffff;
            border-radius: 15px;
            padding: 15px 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, .08);
            margin-bottom: 25px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(180deg, #0ea5e9, #0284c7);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #fff;
            margin-bottom: 15px;
        }
    </style>
    <script>
        document.getElementById('changePasswordForm')
            .addEventListener('submit', function(e) {

                e.preventDefault(); // hentikan submit default

                Swal.fire({
                    title: 'Konfirmasi Perubahan Password',
                    text: 'Apakah Anda yakin ingin mengganti password?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Ganti',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#f59e0b'
                }).then((result) => {
                    if (result.isConfirmed) {
                        e.target.submit(); // submit form manual
                    }
                });
            });
    </script>


</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar p-4">
        <h4 class="mb-4">🎫 MICS IT</h4>

        <a href="dashboard.php">
            <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>
        <a href="add-ticket.php">
            <i class="bi bi-plus-circle me-2"></i> Buat Tiket
        </a>
        <a href="ticket-user.php">
            <i class="bi bi-ticket-detailed me-2"></i> Tiket Saya
        </a>
        <a href="profil.php" class="active">
            <i class="bi bi-person me-2"></i> Profil
        </a>
        <a href="logout.php">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </div>

    <!-- CONTENT -->
    <div class="content">

        <!-- TOP BAR -->
        <div class="topbar d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Profil Saya</h5>
                <small class="text-muted">
                    <?= htmlspecialchars($_SESSION['email'] ?? '-' ) ?></small>
            </div>
            <span class="badge bg-primary">User</span>
        </div>

        <!-- PROFILE CARD -->
        <div class="card card-stat p-4">
            <div class="row align-items-center">

                <!-- LEFT : AVATAR -->
                <div class="col-md-4 d-flex flex-column align-items-center justify-content-center text-center border-end">

                    <div class="profile-avatar mb-3">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>

                    <h5 class="mb-1 fw-semibold">
                        <?= htmlspecialchars($user['name']) ?>
                    </h5>

                    <small class="text-muted">
                        <?= htmlspecialchars($user['email']) ?>
                    </small>

                </div>

                <!-- RIGHT : DETAIL -->
                <div class="col-md-8 ps-md-4">

                    <table class="table table-borderless mb-3">
                        <tr>
                            <th width="35%">Nama Lengkap</th>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                        </tr>
                        <tr>
                            <th>Role</th>
                            <td>
                                <span class="badge bg-info text-dark">
                                    <?= ucfirst($user['role']) ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Bergabung Sejak</th>
                            <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                        </tr>
                    </table>

                    <!-- ACTION BUTTON -->
                    <div class="d-flex gap-2">

                        <!-- <button class="btn btn-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#changeDataModal">
                            <i class="bi bi-pencil-square"></i> Edit Profil
                        </button> -->
                        <button class="btn btn-warning ms-2"
                            data-bs-toggle="modal"
                            data-bs-target="#changePasswordModal">
                            <i class="bi bi-key"></i> Ganti Password
                        </button>
                    </div>

                </div>

            </div>
        </div>

        <div class="modal fade" id="changePasswordModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">

                    <div class="modal-header bg-warning-subtle">
                        <h5 class="modal-title fw-semibold">
                            <i class="bi bi-shield-lock-fill text-warning"></i>
                            Ganti Password
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <form id="changePasswordForm" method="POST">

                        <div class="modal-body">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Password Lama</label>
                                <input type="password" name="old_password"
                                    class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Password Baru</label>
                                <input type="password" name="new_password"
                                    class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Konfirmasi Password Baru</label>
                                <input type="password" name="confirm_password"
                                    class="form-control" required>
                            </div>

                            <input type="hidden" name="change_password" value="1">

                        </div>

                        <div class="modal-footer">
                            <button type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal">
                                Batal
                            </button>

                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-check-circle"></i> Simpan
                            </button>

                        </div>

                    </form>

                </div>
            </div>
        </div>

    </div>
    <?php if (!empty($forceLogout)): ?>
        <script>
            Swal.fire({
                title: 'Password Berhasil Diganti',
                text: 'Demi keamanan, silakan login kembali.',
                icon: 'success',
                confirmButtonText: 'Login Sekarang',
                confirmButtonColor: '#0ea5e9',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(() => {
                window.location.href = 'login.php';
            });
        </script>

        <?php
        // 🔐 HANCURKAN SESSION SETELAH HTML SELESAI
        session_unset();
        session_destroy();
        ?>
    <?php endif; ?>


    <?php if (isset($_SESSION['error'])): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: '<?= htmlspecialchars($_SESSION['error']) ?>',
                confirmButtonColor: '#ef4444'
            });
        </script>
    <?php unset($_SESSION['error']);
    endif; ?>


    <?php if (isset($_SESSION['success'])): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: '<?= htmlspecialchars($_SESSION['success']) ?>',
                confirmButtonColor: '#22c55e'
            });
        </script>
    <?php unset($_SESSION['success']);
    endif; ?>



</body>

</html>