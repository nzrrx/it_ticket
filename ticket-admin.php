<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        t.*,
        u.name AS user_name,
        u.email,
        a.name AS admin_name
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN users a ON t.assigned_to = a.id
    ORDER BY 
        CASE 
            WHEN t.status = 'In Progress' THEN 1
            WHEN t.status = 'Open' THEN 2
            WHEN t.status = 'Closed' THEN 3
            ELSE 4
        END,
        t.created_at DESC
");
$stmt->execute();
$tickets = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Tiket Saya | IT Ticketing</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

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
            background: rgba(255, 255, 255, .2);
        }

        .sidebar h4 {
            font-weight: 700
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: rgba(255, 255, 255, .2)
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

        .btn-btn-primary {
            background: linear-gradient(180deg, #0ea5e9, #0284c7);
            color: #ffffff;
            border: none;
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

        <a href="ticket-admin.php"  class="active">
            <i class="bi bi-ticket-detailed me-2"></i> Semua Tiket
        </a>

        <a href="data-user.php">
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
                <i class="bi bi-ticket-perforated"></i> Semua Tiket
            </h4>

            <a href="add-ticket-admin.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Tambah Tiket
            </a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">

                <?php if ($tickets->num_rows === 0): ?>
                    <div class="alert alert-info text-center">
                        Anda belum memiliki tiket.
                    </div>
                <?php else: ?>

                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>Judul</th>
                                <th>Kategori</th>
                                <th>Prioritas</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Di-Close Oleh</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1;
                            while ($row = $tickets->fetch_assoc()): ?>
                                <tr onclick="window.location='detail-ticket.php?id=<?= $row['id'] ?>'"
                                    style="cursor:pointer;">
                                    <td><?= $no++ ?></td>
                                    <td><?= $row['user_name'] ?></td>
                                    <td><?= htmlspecialchars($row['subject']) ?></td>
                                    <td><?= $row['category'] ?></td>
                                    <td>
                                        <span class="badge bg-<?=
                                                                $row['priority'] == 'High' ? 'danger' : ($row['priority'] == 'Medium' ? 'warning' : 'secondary')
                                                                ?>">
                                            <?= $row['priority'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?=
                                                                $row['status'] == 'Open' ? 'primary' : ($row['status'] == 'In Progress' ? 'info' : ($row['status'] == 'Solved' ? 'success' : 'dark'))
                                                                ?>">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                    <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                    <td>
    <?php if ($row['status'] === 'Closed' && !empty($row['admin_name'])): ?>
        <span class="badge bg-success">
            <?= htmlspecialchars($row['admin_name']) ?>
        </span>
    <?php else: ?>
        <span class="text-muted">-</span>
    <?php endif; ?>
</td>

                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                <?php endif; ?>
            </div>
        </div>
    </div>