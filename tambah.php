<?php
include 'includes/db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role     = "user";

    // VALIDASI SEDERHANA
    if (empty($name) || empty($email) || empty($_POST['password'])) {
        $message = "<p style='color:red'>Semua field wajib diisi.</p>";
    } else {

        $stmt = $conn->prepare(
            "INSERT INTO users (name, email, password, role) 
             VALUES (?, ?, ?, ?)"
        );

        $stmt->bind_param("ssss", $name, $email, $password, $role);

        if ($stmt->execute()) {
            $message = "<p style='color:green'>Registrasi berhasil! Silakan login.</p>";
        } else {
            $message = "<p style='color:red'>Email sudah terdaftar.</p>";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
</head>
<body>

<h3>Register</h3>

<?= $message ?>

<form method="POST">
    <input type="text" name="name" placeholder="Nama Lengkap" required><br><br>
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit">Daftar</button>
</form>

</body>
</html>
