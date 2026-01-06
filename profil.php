<?php
session_start();
include 'includes/db.php';

/* PROTEKSI USER */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

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
    <title>Profil Saya | IT Ticketing</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

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
            background-color: rgba(255,255,255,0.2);
        }

        .content {
            margin-left: 260px;
            padding: 30px;
        }

        .card-stat {
            border: none;
            border-radius: 18px;
            box-shadow: 0 10px 25px rgba(14,165,233,.2);
        }

        .topbar {
            background-color: #ffffff;
            border-radius: 15px;
            padding: 15px 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,.08);
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
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar p-4">
    <h4 class="mb-4">🎫 MICSTIX</h4>

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
            <small class="text-muted"><?= htmlspecialchars($_SESSION['email']) ?></small>
        </div>
        <span class="badge bg-primary">User</span>
    </div>

    <!-- PROFILE CARD -->
    <div class="card card-stat p-4">
        <div class="row align-items-center">
            <div class="col-md-4 text-center">
                <div class="profile-avatar">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
                <h5 class="mb-0"><?= htmlspecialchars($user['name']) ?></h5>
                <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
            </div>

            <div class="col-md-8">
                <table class="table table-borderless">
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
            </div>
        </div>
    </div>

</div>

</body>
</html>
