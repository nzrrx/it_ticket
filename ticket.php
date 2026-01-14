<?php
session_start();
include 'includes/db.php';

if (! isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$admin_id = (int) $_SESSION['user_id'];

/* TOTAL TIKET */
$total = $conn->query("SELECT COUNT(*) total FROM tickets")->fetch_assoc()['total'];

/* OPEN */
$open = $conn->query("
    SELECT COUNT(*) total FROM tickets WHERE status='Open'
")->fetch_assoc()['total'];

/* IN PROGRESS */
$process = $conn->query("
    SELECT COUNT(*) total FROM tickets WHERE status='In Progress'
")->fetch_assoc()['total'];

/* CLOSED */
$closed = $conn->query("
    SELECT COUNT(*) total FROM tickets WHERE status='Closed'
")->fetch_assoc()['total'];

/* TIKET TERBARU */
$latest = $conn->query("
    SELECT t.id, t.subject, t.status, t.created_at, u.name
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    ORDER BY t.created_at DESC
    LIMIT 5
    ");
    
    /* HITUNG PESAN BARU (TANPA is_read) */
$stmtNotif = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM ticket_replies m
    JOIN tickets t ON m.ticket_id = t.id
    WHERE m.user_id != ?
      AND m.is_read = 0
");
$stmtNotif->bind_param("i", $admin_id);
$stmtNotif->execute();
$unreadMessage = $stmtNotif->get_result()->fetch_assoc()['total'];

/* PREVIEW PESAN (MAX 2 HARI TERAKHIR) */
$stmtPreview = $conn->prepare("
    SELECT 
        m.id,
        m.ticket_id,
        m.message,
        m.created_at,
        u.name
    FROM ticket_replies m
    JOIN users u ON m.user_id = u.id
    WHERE u.role = 'user'
      AND m.is_read = 0
    ORDER BY m.created_at DESC
    LIMIT 5
");
$stmtPreview->execute();
$previewMessages = $stmtPreview->get_result();


?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | MICS IT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: #f0f9ff;
            font-family: 'Segoe UI', sans-serif
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            background: linear-gradient(180deg, #0ea5e9, #0284c7);
            position: fixed;
            color: #fff
        }

        .sidebar h4 {
            font-weight: 700
        }

        .sidebar a {
            color: #e0f2fe;
            text-decoration: none;
            display: block;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 5px;
            transition: .3s
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: rgba(255, 255, 255, .2)
        }

        .content {
            margin-left: 260px;
            padding: 30px
        }

        .card-stat {
            border: none;
            border-radius: 18px;
            box-shadow: 0 10px 25px rgba(14, 165, 233, .2)
        }

        .card-stat i {
            font-size: 2rem;
            color: #0ea5e9
        }

        .topbar {
            background: #fff;
            border-radius: 15px;
            padding: 15px 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, .08);
            margin-bottom: 25px
        }

        .badge-open {
            background: #3b82f6
        }

        .badge-process {
            background: #f59e0b
        }

        .badge-closed {
            background: #22c55e
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar p-4">
        <h4 class="mb-4">🛠️ MICS IT</h4>

        <a href="ticket.php" class="active">
            <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>

        <a href="ticket-admin.php">
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

    <!-- CONTENT -->
    <div class="content">

        <!-- TOP BAR -->
        <div class="topbar d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Dashboard Admin 👋</h5>
                <small class="text-muted"><?php echo $_SESSION['email']; ?></small>
            </div>

            <div class="d-flex align-items-center gap-3">

    
<!-- DROPDOWN PESAN -->
<div class="dropdown">
    <a href="#" id="messageDropdown"
       class="text-decoration-none text-dark position-relative"
       data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-chat-dots fs-5"></i>

        <?php if ($unreadMessage > 0): ?>
            <span id="messageBadge"
                  class="position-absolute top-0 start-100 translate-middle
                         badge rounded-pill bg-danger">
                <?= $unreadMessage ?>
            </span>
        <?php endif; ?>
    </a>

    <div class="dropdown-menu dropdown-menu-end shadow p-2"
         style="width: 320px;">

        <h6 class="dropdown-header">Pesan Terbaru</h6>

        <?php if ($previewMessages->num_rows > 0): ?>
            <?php while ($msg = $previewMessages->fetch_assoc()): ?>
                <a href="detail-ticket.php?id=<?= $msg['ticket_id']; ?>"
   class="dropdown-item small py-2 message-item"
   data-message-id="<?= $msg['id']; ?>">


                    <div class="d-flex justify-content-between">
                        <strong><?= htmlspecialchars($msg['name']); ?></strong>
                        <small class="text-muted">
                            <?= date('d M H:i', strtotime($msg['created_at'])); ?>
                        </small>
                    </div>

                    <div class="text-muted small">
                        Tiket #<?= $msg['ticket_id']; ?>
                    </div>

                    <div class="text-muted">
                        <?= htmlspecialchars(substr($msg['message'], 0, 45)); ?>…
                    </div>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <span class="dropdown-item text-muted small">
                Tidak ada pesan baru
            </span>
        <?php endif; ?>

        <div class="dropdown-divider"></div>
        <a href="ticket-admin.php"
           class="dropdown-item text-center small fw-semibold">
            Lihat semua tiket
        </a>
    </div>
</div>

            <span class="badge bg-danger">ADMIN</span>
            </div>
        </div>
        

        <!-- STAT CARDS -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card card-stat p-4">
                    <i class="bi bi-ticket-perforated mb-2"></i>
                    <h4><?php echo $total ?></h4>
                    <p class="text-muted mb-0">Total Tiket</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-stat p-4">
                    <i class="bi bi-exclamation-circle mb-2"></i>
                    <h4><?php echo $open ?></h4>
                    <p class="text-muted mb-0">Tiket Open</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-stat p-4">
                    <i class="bi bi-hourglass-split mb-2"></i>
                    <h4><?php echo $process ?></h4>
                    <p class="text-muted mb-0">In Progress</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-stat p-4">
                    <i class="bi bi-check-circle mb-2"></i>
                    <h4><?php echo $closed ?></h4>
                    <p class="text-muted mb-0">Closed</p>
                </div>
            </div>
        </div>

        <!-- TIKET TERBARU -->
        <div class="card p-4 card-stat">
            <h5 class="mb-3">📌 Tiket Terbaru</h5>

            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Subject</th>
                        <th>User</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($t = $latest->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $t['id'] ?></td>
                            <td><?php echo htmlspecialchars($t['subject']) ?></td>
                            <td><?php echo $t['name'] ?></td>
                            <td>
                                <span class="badge bg-<?=
                                                                $t['status'] == 'Open' ? 'primary' : ($t['status'] == 'In Progress' ? 'info' : ($t['status'] == 'Solved' ? 'success' : 'dark'))
                                                                ?>">
                                            <?= $t['status'] ?>
                                        </span>
                            </td>
                            <td><?php echo date('d M Y', strtotime($t['created_at'])) ?></td>
                            <td>
                                <a href="detail-ticket.php?id=<?php echo $t['id'] ?>" class="btn btn-sm">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </div>
    <script>
document.getElementById('messageDropdown')
    .addEventListener('show.bs.dropdown', function () {

        //preview saja
    });
</script>
<script>
document.querySelectorAll('.message-item').forEach(item => {
    item.addEventListener('click', function () {
        const messageId = this.dataset.messageId;

        fetch('mark-read-admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'message_id=' + messageId
        });
    });
});
</script>


</body>



</html>