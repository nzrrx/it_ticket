<?php
session_start();
include 'includes/db.php';

/* =====================
   PROTEKSI ADMIN
===================== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
$admin_id = (int) $_SESSION['user_id'];

/* =====================
   VALIDASI ID
===================== */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ticket.php");
    exit;
}

$ticket_id = (int) $_GET['id'];

/* =====================
   AMBIL DETAIL TIKET
===================== */
$stmt = $conn->prepare("
    SELECT 
        t.*, 
        u.name AS user_name,
        u.email AS user_email
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ?
");
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();

if (!$ticket) {
    echo "Ticket tidak ditemukan.";
    exit;
}



/* =====================
   UPDATE STATUS & NOTE
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status']) && !isset($_POST['message'])) {

    $status = $_POST['status'];

    // ❌ Jika tiket sudah Closed & bukan emergency → BLOK
    if ($ticket['status'] === 'Closed' && empty($_POST['emergency'])) {
        header("Location: detail-ticket.php?id={$ticket_id}&error=locked");
        exit;
    }


    // =====================
    // STATUS: IN PROGRESS
    // =====================
    if ($status === 'In Progress') {

        $update = $conn->prepare("
            UPDATE tickets
            SET status = 'In Progress',
                assigned_to = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $update->bind_param("ii", $admin_id, $ticket_id);
        $update->execute();
    }

    // =====================
    // STATUS: CLOSED
    // =====================
    elseif ($status === 'Closed') {

        $update = $conn->prepare("
            UPDATE tickets
            SET status = 'Closed',
                assigned_to = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $update->bind_param("ii", $admin_id, $ticket_id);
        $update->execute();
    }

    // =====================
    // STATUS: OPEN (RESET)
    // =====================
    elseif ($status === 'Open') {

        $update = $conn->prepare("
            UPDATE tickets
            SET status = 'Open',
                assigned_to = NULL,
                updated_at = NOW()
            WHERE id = ?
        ");
        $update->bind_param("i", $ticket_id);
        $update->execute();
    }

    header("Location: detail-ticket.php?id={$ticket_id}");
    exit;
}

/* =====================
   SIMPAN CHAT ADMIN
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {

    if ($ticket['status'] === 'Closed') {
        header("Location: detail-ticket.php?id=" . $ticket_id . "&locked=1");
        exit;
    }

    $message = trim($_POST['message']);

    if ($message !== '') {
        $save = $conn->prepare("
            INSERT INTO ticket_replies (ticket_id, user_id, message)
            VALUES (?, ?, ?)
        ");
        $save->bind_param("iis", $ticket_id, $admin_id, $message);
        $save->execute();

        header("Location: detail-ticket.php?id=" . $ticket_id);
        exit;
    }
}

/* =====================
   AMBIL RIWAYAT CHAT
===================== */
$replies = $conn->prepare("
    SELECT r.message, r.created_at, u.name, u.role
    FROM ticket_replies r
    JOIN users u ON r.user_id = u.id
    WHERE r.ticket_id = ?
    ORDER BY r.created_at ASC
");

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
$stmt->bind_param("ii", $ticket_id, $admin_id);
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

        .sidebar h4 {
            font-weight: 700;
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
        <h4 class="mb-4">🛠️ MICS IT</h4>

        <a href="ticket.php">
            <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>

        <a href="ticket-admin.php" class="active">
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

        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-0">Detail Ticket #<?= $ticket['id'] ?></h5>
                <small class="text-muted"><?= htmlspecialchars($ticket['user_email']) ?></small>
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
                            <td><?= htmlspecialchars($ticket['user_name']) ?></td>
                        </tr>
                        <tr>
                            <th>Judul</th>
                            <td><?= htmlspecialchars($ticket['subject']) ?></td>
                        </tr>
                        <tr>
                            <th>Kategori</th>
                            <td><?= $ticket['category'] ?></td>
                        </tr>
                        <tr>
                            <th>Prioritas</th>
                            <td>
                                <span class="badge bg-<?=
                                                        $ticket['priority'] == 'High' ? 'danger' : ($ticket['priority'] == 'Medium' ? 'warning' : 'secondary')
                                                        ?>">
                                    <?= $ticket['priority'] ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Deskripsi</th>
                            <td><?= nl2br(htmlspecialchars($ticket['description'])) ?></td>
                        </tr>
                        <tr>
                            <th>Dibuat</th>
                            <td><?= date('d M Y H:i', strtotime($ticket['created_at'])) ?></td>
                        </tr>
                        <tr>
                            <th>Terakhir Update</th>
                            <td><?= date('d M Y H:i', strtotime($ticket['updated_at'])) ?></td>
                        </tr>
                        <?php if (!empty($ticket['attachment'])) : ?>
                            <tr>
                                <th>Attachment</th>
                                <td>
                                    <a href="../download.php?file=<?= urlencode($ticket['attachment']) ?>"
                                        target="_blank"
                                        class="text-primary text-decoration-none">
                                        <i class="bi bi-eye"></i> Lihat Attachment
                                    </a>
                                </td>
                            </tr>
                        <?php else : ?>
                            <tr>
                                <th>Attachment</th>
                                <td><span class="text-muted">Tidak ada attachment</span></td>
                            </tr>
                        <?php endif; ?>

                    </table>
                </div>
            </div>

            <!-- AKSI ADMIN -->
            <div class="col-md-4">
                <div class="card p-4">
                    <h5 class="mb-3">⚙️ Aksi Admin</h5>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="Open" <?= $ticket['status'] == 'Open' ? 'selected' : '' ?>>Open</option>
                                <option value="In Progress" <?= $ticket['status'] == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="Closed" <?= $ticket['status'] == 'Closed' ? 'selected' : '' ?>>Closed</option>
                            </select>
                            <!-- <input type="hidden" name="emergency" value="1"> -->
                        </div>

                        <button class="btn btn-primary w-100">
                            <i class="bi bi-save"></i> Simpan Perubahan
                        </button>
                    </form>

                    <hr>


                    <a href="ticket-admin.php" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>


            <!-- PERCAPAKAN -->
            <div class="col-md">
                <div class="card p-4">
                    <h5 class="mb-3">💬 Percakapan</h5>

                    <div style="max-height:350px; overflow-y:auto">

                        <?php if ($chats->num_rows > 0): ?>
                            <?php while ($c = $chats->fetch_assoc()): ?>


                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <strong>
                                            <?php echo htmlspecialchars($c['name']) ?>
                                            <?php if ($c['role'] == 'admin'): ?>
                                                <span class="badge bg-danger ms-1">ADMIN</span>
                                            <?php endif; ?>
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
                            <?= $ticket['status'] === 'Closed' ? 'disabled' : 'required' ?>></textarea>

                        <button
                            class="btn w-100 <?= $ticket['status'] === 'Closed' ? 'btn-secondary' : 'btn-primary' ?>"
                            <?= $ticket['status'] === 'Closed' ? 'disabled' : '' ?>>
                            <i class="bi <?= $ticket['status'] === 'Closed' ? 'bi-lock-fill' : 'bi-send' ?>"></i>
                            <?= $ticket['status'] === 'Closed' ? 'Percakapan Dikunci' : 'Kirim Pesan' ?>
                        </button>

                    </form>

                </div>

            </div>
</body>

</html>