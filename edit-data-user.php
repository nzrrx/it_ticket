<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: data-user.php");
    exit;
}

$id = $_GET['id'];

$stmt = $conn->prepare("
    SELECT id, name, email, role
    FROM users
    WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: data-user.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role  = $_POST['role'];

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            UPDATE users
            SET name=?, email=?, role=?, password=?
            WHERE id=?
        ");
        $stmt->bind_param("ssssi", $name, $email, $role, $password, $id);
    } else {
        $stmt = $conn->prepare("
            UPDATE users
            SET name=?, email=?, role=?
            WHERE id=?
        ");
        $stmt->bind_param("sssi", $name, $email, $role, $id);
    }

    $stmt->execute();
    header("Location: data-user.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title> Edit User | MICS IT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<script src="bootstrap.bundle.min.js"></script>


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
.sidebar a {
    color: #e0f2fe;
    text-decoration: none;
    display: block;
    padding: 12px 20px;
    border-radius: 10px;
    margin-bottom: 5px;
    transition: .3s;
}
.sidebar a.active,
.sidebar a:hover {
    background: rgba(255,255,255,.2);
}

.sidebar h4{font-weight:700}

.sidebar a:hover,.sidebar a.active{
    background:rgba(255,255,255,.2)
}
.content {
    margin-left: 260px;
    padding: 30px;
}
.badge-status {
    padding: 6px 12px;
    border-radius: 12px;
    font-size: 0.8rem;
}
</style>
    <title>Data User</title>
</head>
<body>

    <!-- SIDEBAR -->
<div class="sidebar p-4">
<h4 class="mb-4">🛠️ MICS IT</h4>

<a href="ticket.php">
<i class="bi bi-speedometer2 me-2"></i> Dashboard
</a>

<a href="ticket-admin.php">
<i class="bi bi-ticket-detailed me-2"></i> Semua Tiket
</a>

<a href="data-user.php"  class="active">
<i class="bi bi-people me-2"></i> Data User
</a>

<a href="#">
<i class="bi bi-gear me-2"></i> Pengaturan
</a>

<a href="../logout.php">
<i class="bi bi-box-arrow-right me-2"></i> Logout
</a>
</div>



<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
            <i class="bi bi-ticket-perforated"></i> Data User
        </h4>
    </div>

        <div class="container mt-5">
<div class="card shadow-sm border-0">
<div class="card-header bg-primary text-white">
    <i class="bi bi-pencil-square"></i> Edit Data User
</div>

<div class="card-body">
<form id="editUserForm" method="POST">

<div class="mb-3">
    <label class="form-label fw-semibold">Nama</label>
    <input type="text" name="name" class="form-control"
           value="<?= htmlspecialchars($user['name']) ?>" required>
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">Email</label>
    <input type="email" name="email" class="form-control"
           value="<?= htmlspecialchars($user['email']) ?>" required>
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">Role</label>
    <select name="role" class="form-select">
        <option value="user" <?= $user['role']=='user'?'selected':'' ?>>User</option>
        <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
    </select>
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">
        Password Baru <small class="text-muted">(opsional)</small>
    </label>
    <input type="password" name="password" class="form-control"
           placeholder="Kosongkan jika tidak diubah">
</div>

<div class="d-flex justify-content-between">
    <a href="data-user.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
    <button type="button"
            class="btn btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#confirmSaveModal">
        <i class="bi bi-save"></i> Simpan Perubahan
    </button>
</div>

</form>
<!-- MODAL KONFIRMASI SIMPAN -->
<div class="modal fade" id="confirmSaveModal" tabindex="-1"
     aria-labelledby="confirmSaveLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">

      <div class="modal-header bg-warning-subtle">
        <h5 class="modal-title fw-semibold" id="confirmSaveLabel">
          <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
          Konfirmasi Perubahan Data
        </h5>
        <button type="button" class="btn-close"
                data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <p class="mb-2">
          Anda akan menyimpan <strong>perubahan data user</strong>.
        </p>
        <p class="text-muted small mb-0">
          Pastikan data yang diubah sudah benar.  
          Kesalahan pengeditan dapat mempengaruhi hak akses pengguna.
        </p>
      </div>

      <div class="modal-footer">
        <button type="button"
                class="btn btn-outline-secondary"
                data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i> Batal
        </button>

        <button type="button"
                class="btn btn-primary"
                id="confirmSubmitBtn">
          <i class="bi bi-check-circle"></i> Ya, Simpan
        </button>
      </div>

    </div>
  </div>
</div>


</div>
</div>
        </div>
</div>
<script>
document.getElementById('confirmSubmitBtn')
    .addEventListener('click', function () {

        // Disable tombol agar tidak double submit
        this.disabled = true;
        this.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';

        document.getElementById('editUserForm').submit();
    });
</script>

</body>
</html>