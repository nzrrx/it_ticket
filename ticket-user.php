<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil tiket milik user ini saja
$stmt = $conn->prepare("
    SELECT 
    t.id,
    t.subject,
    t.category,
    t.priority,
    t.status,
    t.created_at,
    t.attachment,
    u.name AS user_name,
    u.email,
    a.name AS admin_name
FROM tickets t
JOIN users u ON t.user_id = u.id
LEFT JOIN users a ON t.assigned_to = a.id
WHERE t.user_id = ?
ORDER BY 
    CASE 
        WHEN t.status = 'In Progress' THEN 1
        WHEN t.status = 'Open' THEN 2
        WHEN t.status = 'Closed' THEN 3
        ELSE 4
    END,
    t.created_at DESC;
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tickets = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Tiket Saya | MICS IT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


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
        }

        .sidebar h4 {
            font-weight: 700;
        }

        .sidebar a.active,
        .sidebar a:hover {
            background: rgba(255, 255, 255, .2);
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
        <a href="ticket-user.php" class="active">
            <i class="bi bi-ticket-detailed me-2"></i> Tiket Saya
        </a>
        <a href="profil.php">
            <i class="bi bi-person me-2"></i> Profil
        </a>
        <a href="logout.php">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </div>

    <div class="content">
        <h4 class="mb-4">
            <i class="bi bi-ticket-perforated"></i> Tiket Saya
        </h4>

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
                                <th>Judul</th>
                                <th>Kategori</th>
                                <th>Prioritas</th>
                                <th>Status</th>
                                <th>Dibuat</th>
                                <th>Jam</th>
                                <th>Checked By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1;
                            while ($row = $tickets->fetch_assoc()): ?>
                                <tr onclick="window.location='detail-ticket-user.php?id=<?= $row['id'] ?>'"
                                    style="cursor:pointer;">
                                    <td><?= $no++ ?></td>
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
                                    <td><?php echo date('H:i', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <?php if (
                                            in_array($row['status'], ['Closed', 'In Progress'])
                                            && !empty($row['admin_name'])
                                        ): ?>
                                            <span class="badge bg-success">
                                                <?= htmlspecialchars($row['admin_name']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>


                                <?php endwhile; ?>
                        </tbody>
                    </table>

                <?php endif; ?>
            </div>
        </div>
    </div>