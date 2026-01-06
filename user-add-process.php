<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$name     = trim($_POST['name']);
$email    = trim($_POST['email']);
$password = $_POST['password'];
$role     = $_POST['role'];

/* VALIDASI DASAR */
if (empty($name) || empty($email) || empty($password) || empty($role)) {
    $_SESSION['flash_error'] = 'Semua field wajib diisi.';
    header("Location: user-add.php");
    exit;
}

/* HASH PASSWORD */
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

/* CEK EMAIL DUPLIKAT */
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $_SESSION['flash_error'] = 'Email sudah terdaftar.';
    header("Location: user-add.php");
    exit;
}

/* SIMPAN USER */
$stmt = $conn->prepare("
    INSERT INTO users (name, email, password, role, created_at)
    VALUES (?, ?, ?, ?, NOW())
");
$stmt->bind_param("ssss", $name, $email, $passwordHash, $role);
$stmt->execute();

/* FLASH SUCCESS */
$_SESSION['flash_success'] = 'User berhasil dibuat.';

header("Location: user-add.php");
exit;
