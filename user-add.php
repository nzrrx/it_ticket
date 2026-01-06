<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Tambah User | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
body{
    background:#f0f9ff;
    font-family:'Segoe UI',sans-serif;
}
.sidebar{
    width:250px;
    height:100vh;
    background:linear-gradient(180deg,#0ea5e9,#0284c7);
    position:fixed;
    color:#fff;
}
.sidebar h4{
    font-weight:700;
}
.sidebar a{
    color:#e0f2fe;
    text-decoration:none;
    display:block;
    padding:12px 20px;
    border-radius:10px;
    margin-bottom:5px;
    transition:.3s;
}
.sidebar a:hover,
.sidebar a.active{
    background:rgba(255,255,255,.2);
}
.content{
    margin-left:260px;
    padding:30px;
}
.card{
    border:none;
    border-radius:18px;
    box-shadow:0 10px 25px rgba(14,165,233,.2);
}
.card card-form p-4 {
    padding: 30px;
    text-align: center;
}
</style>
</head>

<body>

    <!-- SIDEBAR -->
<div class="sidebar p-4">
<h4 class="mb-4">🛠️ MICSTIX</h4>

<a href="ticket.php">
<i class="bi bi-speedometer2 me-2"></i> Dashboard
</a>

<a href="ticket-admin.php">
<i class="bi bi-ticket-detailed me-2"></i> Semua Tiket
</a>

<a href="data-user.php" class="active">
<i class="bi bi-people me-2"></i> Data User
</a>

<a href="#">
<i class="bi bi-gear me-2"></i> Pengaturan
</a>

<a href="../logout.php">
<i class="bi bi-box-arrow-right me-2"></i> Logout
</a>
</div>

<!-- CONTENT -->
<div class="content">


<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">
        <i class="bi bi-person-plus"></i> Tambah User
    </h4>

    <a href="data-user.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<div class="row justify-content-center">
        <div class="col-lg-5">

            <div class="card card-form p-4">

<?php if (isset($_SESSION['flash_success'])) : ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <strong>Sukses!</strong> <?= $_SESSION['flash_success']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['flash_success']); endif; ?>


<?php if (isset($_SESSION['flash_error'])) : ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong>Gagal!</strong> <?= $_SESSION['flash_error']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['flash_error']); endif; ?>

<form method="POST" action="user-add-process.php">

    <div class="mb-3">
        <label class="form-label">Nama Lengkap</label>
        <input type="text" name="name" class="form-control" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>

    <div class="mb-4">
        <label class="form-label">Role</label>
        <select name="role" class="form-select" required>
            <option value="user">User</option>
            <option value="admin">Admin</option>
        </select>
    </div>

    <button class="btn btn-primary w-100">
        <i class="bi bi-save"></i> Simpan User
    </button>

</form>

</div>
</div>
</div>

</body>
</html>
