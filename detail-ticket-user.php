<?php
    session_start();
    include 'includes/db.php';

    /* =====================
   PROTEKSI USER
===================== */
    if (! isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
        header("Location: login.php");
        exit;
    }

    $user_id = (int) $_SESSION['user_id'];

    /* =====================
   VALIDASI ID TIKET
===================== */
    if (! isset($_GET['id']) || ! is_numeric($_GET['id'])) {
        header("Location: ticket-user.php");
        exit;
    }

    $ticket_id = (int) $_GET['id'];

    /* =====================
   AMBIL DETAIL TIKET (KHUSUS MILIK USER)
===================== */
    $stmt = $conn->prepare("
    SELECT t.*,
    u.name AS user_name,
        u.email AS user_email
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ? AND t.user_id = ?
");
    $stmt->bind_param("ii", $ticket_id, $user_id);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();

    if (! $ticket) {
        echo "Ticket tidak ditemukan.";
        exit;
    }

    /* =====================
   SIMPAN KOMENTAR USER
   (CEK STATUS CLOSED)
===================== */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {

        // JIKA TIKET SUDAH CLOSED → STOP
        if ($ticket['status'] === 'Closed') {
            header("Location: detail-ticket-user.php?id=" . $ticket_id . "&closed=1");
            exit;
        }

        $message = trim($_POST['message']);

        if ($message !== '') {
            $save = $conn->prepare("
            INSERT INTO ticket_replies (ticket_id, user_id, message)
            VALUES (?, ?, ?)
        ");
            $save->bind_param("iis", $ticket_id, $user_id, $message);
            $save->execute();

            header("Location: detail-ticket-user.php?id=" . $ticket_id);
            exit;
        }
    }

    /* =====================
   AMBIL RIWAYAT KOMENTAR
===================== */
    $replies = $conn->prepare("
    SELECT r.message, r.created_at, u.name, u.role
    FROM ticket_replies r
    JOIN users u ON r.user_id = u.id
    WHERE r.ticket_id = ?
    ORDER BY r.created_at ASC
");
    $replies->bind_param("i", $ticket_id);
    $replies->execute();
    $chats = $replies->get_result();

//mark read
$replies->bind_param("i", $ticket_id);
$replies->execute();
$chats = $replies->get_result();

$ticket_id = (int) $_GET['id'];

$stmt = $conn->prepare("
    UPDATE ticket_replies
    SET is_read = 1
    WHERE ticket_id = ?
      AND user_id != ?
      AND is_read = 0
");
$stmt->bind_param("ii", $ticket_id, $user_id);
$stmt->execute();    
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Detail Ticket | MICS IT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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

        .sidebar a {
            color: #e0f2fe;
            text-decoration: none;
            display: block;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 5px
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: rgba(255, 255, 255, .2)
        }

        .content {
            margin-left: 260px;
            padding: 30px
        }

        .card {
            border: none;
            border-radius: 18px;
            box-shadow: 0 10px 25px rgba(14, 165, 233, .2)
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
        <h4 class="mb-4">🎫 MICS IT</h4>

        <a href="dashboard.php">
            <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>
        <a href="add-ticket.php">
            <i class="bi bi-plus-circle me-2"></i> Buat Tiket
        </a>
        <a href="ticket-user.php"  class="active">
            <i class="bi bi-ticket-detailed me-2"></i> Tiket Saya
        </a>
        <a href="#">
            <i class="bi bi-person me-2"></i> Profil
        </a>
        <a href="logout.php">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </div>

    <!-- CONTENT -->
    <div class="content">

        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-0">Detail Ticket #<?php echo $ticket['id'] ?></h5>
                <small class="text-muted"><?php echo htmlspecialchars($ticket['user_name']) ?></small>
            </div>
            <span class="badge bg-<?=
                                    $ticket['status'] == 'Open' ? 'primary' : ($ticket['status'] == 'In Progress' ? 'info' : ($ticket['status'] == 'Solved' ? 'success' : 'dark'))
                                    ?>">
                <?= $ticket['status'] ?>
            </span>
        </div>

        <div class="row g-4">

            <!-- INFORMASI TIKET -->
            <div class="col-md-8">
                <div class="card p-4">
                    <h5 class="mb-3">📄 Informasi Ticket</h5>

                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">Nama User</th>
                            <td><?php echo htmlspecialchars($ticket['user_name']) ?></td>
                        </tr>
                        <tr>
                            <th>Judul</th>
                            <td><?php echo htmlspecialchars($ticket['subject']) ?></td>
                        </tr>
                        <tr>
                            <th>Kategori</th>
                            <td><?php echo $ticket['category'] ?></td>
                        </tr>
                        <tr>
                            <th>Prioritas</th>
                            <td>
                                <span class="badge bg-<?php echo
                                                      $ticket['priority'] == 'High' ? 'danger' : ($ticket['priority'] == 'Medium' ? 'warning' : 'secondary')
                                                      ?>">
                                    <?php echo $ticket['priority'] ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Deskripsi</th>
                            <td><?php echo nl2br(htmlspecialchars($ticket['description'])) ?></td>
                        </tr>
                        <tr>
                            <th>Dibuat</th>
                            <td><?php echo date('d M Y H:i', strtotime($ticket['created_at'])) ?></td>
                        </tr>
                        <tr>
                            <th>Terakhir Update</th>
                            <td><?php echo date('d M Y H:i', strtotime($ticket['updated_at'])) ?></td>
                        </tr>
                        <?php if (! empty($ticket['attachment'])): ?>
                            <tr>
                                <th>Attachment</th>
                                <td>
                                    <a href="../download.php?file=<?php echo urlencode($ticket['attachment']) ?>"
                                        target="_blank"
                                        class="text-primary text-decoration-none">
                                        <i class="bi bi-eye"></i> Lihat Attachment
                                    </a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <th>Attachment</th>
                                <td><span class="text-muted">Tidak ada attachment</span></td>
                            </tr>
                        <?php endif; ?>

                    </table>
                </div>
            </div>

            <!-- PERCAPAKAN -->
<div class="col-md-4">
    <div class="card p-4">
        <a href="ticket-user.php" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                    <hr>
        <h5 class="mb-3">💬 Percakapan</h5>

        <div style="max-height:350px; overflow-y:auto">

            <?php if ($chats->num_rows > 0): ?>
                <?php while ($c = $chats->fetch_assoc()): ?>


                <div class="mb-3">
    <div class=" justify-content-between">
        <strong>
            <?php echo htmlspecialchars($c['name']) ?>
            <!-- <?php if ($c['role'] == 'admin'): ?>
                <span class="badge bg-danger ms-1">ADMIN</span>
            <?php endif; ?> -->
        </strong>
        <small class="text-muted">
            <?php echo date('l, d M Y H:i', strtotime($c['created_at'])) ?>
        </small>
    </div>

    <div class="p-3 mt-1 rounded
        <?php echo $c['role'] == 'admin'
            ? 'bg-light border-start border-4 border-danger'
            : 'bg-white border-start border-4 border-primary' ?>">
        <?php echo nl2br(htmlspecialchars($c['message'])) ?>
    </div>
</div>


                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-muted text-center">Belum ada percakapan</p>
            <?php endif; ?>

        </div>

        <hr>

        <!-- FORM BALAS -->
        <form method="POST">

    <textarea
        name="message"
        class="form-control mb-3 <?= $ticket['status'] === 'Closed' ? 'bg-light' : '' ?>"
        rows="3"
        placeholder="<?= $ticket['status'] === 'Closed'
            ? '🔒 Percakapan dikunci. Tiket telah ditutup.'
            : 'Tulis pesan untuk admin...' ?>"
        <?= $ticket['status'] === 'Closed' ? 'disabled' : 'required' ?>
    ></textarea>

    <button
        class="btn w-100 <?= $ticket['status'] === 'Closed' ? 'btn-secondary' : 'btn-primary' ?>"
        <?= $ticket['status'] === 'Closed' ? 'disabled' : '' ?>
    >
        <i class="bi <?= $ticket['status'] === 'Closed' ? 'bi-lock-fill' : 'bi-send' ?>"></i>
        <?= $ticket['status'] === 'Closed' ? 'Percakapan Dikunci' : 'Kirim Pesan' ?>
    </button>

</form>

    </div>
</div>


        </div>

    </div>
</body>

</html>