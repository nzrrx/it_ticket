
<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

/* TOTAL TIKET */
$stmtTotal = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM tickets
    WHERE user_id = ?
");
$stmtTotal->bind_param("i", $user_id);
$stmtTotal->execute();
$totalTicket = $stmtTotal->get_result()->fetch_assoc()['total'];

/* TIKET OPEN */
$stmtProcess = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM tickets
    WHERE user_id = ? AND status = 'Open'
");
$stmtProcess->bind_param("i", $user_id);
$stmtProcess->execute();
$openTicket = $stmtProcess->get_result()->fetch_assoc()['total'];


/* TIKET DALAM PROSES */
$stmtProcess = $conn->prepare("
    SELECT COUNT(*) AS process
    FROM tickets
    WHERE user_id = ? AND status = 'In Progress'
");

$stmtProcess->bind_param("i", $user_id);
$stmtProcess->execute();
$processTicket = $stmtProcess->get_result()->fetch_assoc()['process'];

/* TIKET SELESAI */
$stmtDone = $conn->prepare("
    SELECT COUNT(*) AS done
    FROM tickets
    WHERE user_id = ? AND status = 'Closed'
");
$stmtDone->bind_param("i", $user_id);
$stmtDone->execute();
$doneTicket = $stmtDone->get_result()->fetch_assoc()['done'];

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
    WHERE t.user_id = ?
      AND m.user_id != ?
      AND m.is_read = 0
");
$stmtNotif->bind_param("ii", $user_id, $user_id);
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
    JOIN tickets t ON m.ticket_id = t.id
    JOIN users u ON m.user_id = u.id
    WHERE t.user_id = ?
      AND m.user_id != ?
      AND m.is_read = 0
    ORDER BY m.created_at DESC
    LIMIT 5
");
$stmtPreview->bind_param("ii", $user_id, $user_id);
$stmtPreview->execute();
$previewMessages = $stmtPreview->get_result();


?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | IT Ticketing</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


    <!-- Bootstrap Icons -->
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

        .card-stat i {
            font-size: 2rem;
            color: #0ea5e9;
        }

        .topbar {
            background-color: #ffffff;
            border-radius: 15px;
            padding: 15px 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,.08);
            margin-bottom: 25px;
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar p-4">
    <h4 class="mb-4">🎫 MICSTIX</h4>

    <a href="dashboard.php"  class="active">
        <i class="bi bi-speedometer2 me-2"></i> Dashboard
    </a>
    <a href="add-ticket.php">
        <i class="bi bi-plus-circle me-2"></i> Buat Tiket
    </a>
    <a href="ticket-user.php">
        <i class="bi bi-ticket-detailed me-2"></i> Tiket Saya
    </a>
    <a href="profil.php">
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
        <h5 class="mb-0">Selamat datang 👋</h5>
        <small class="text-muted"><?= $_SESSION['email']; ?></small>
    </div>

    <div class="d-flex align-items-center gap-3">

        <!-- DROPDOWN PESAN -->
<div class="dropdown">
    <a href="#" class="text-decoration-none text-dark position-relative"
       data-bs-toggle="dropdown">
        <i class="bi bi-chat-dots fs-5"></i>

        <?php if ($unreadMessage > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle
                         badge rounded-pill bg-danger">
                <?= $unreadMessage ?>
            </span>
        <?php endif; ?>
    </a>

    <div class="dropdown-menu dropdown-menu-end shadow p-2"
         style="width: 300px;">

        <h6 class="dropdown-header">Pesan Terbaru</h6>

        <?php if ($previewMessages->num_rows > 0): ?>
            <?php while ($msg = $previewMessages->fetch_assoc()): ?>
                <a href="ticket-user.php"
                   class="dropdown-item small">
                    <strong><?= htmlspecialchars($msg['name']); ?></strong>
                    <small class="text-muted">
            <?php echo date('l, d M Y H:i', strtotime($msg['created_at'])) ?>
        </small>
        <br>
                    <span class="text-muted">
                        <?= htmlspecialchars(substr($msg['message'], 0, 40)); ?>...
                    </span>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <span class="dropdown-item text-muted small">
                Tidak ada pesan baru
            </span>
        <?php endif; ?>

        <div class="dropdown-divider"></div>
        <a href="ticket-user.php" class="dropdown-item text-center small fw-semibold">
            Lihat semua tiket
        </a>
    </div>
</div>




        <!-- STATUS ONLINE -->
        <span class="badge bg-primary">Online</span>

    </div>
</div>


        <!-- STAT CARDS -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card card-stat p-4">
                    <i class="bi bi-ticket-perforated mb-2"></i>
                    <h4><?php echo $totalTicket ?></h4>
                    <p class="text-muted mb-0">Total Tiket</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-stat p-4">
                    <i class="bi bi-exclamation-circle mb-2"></i>
                    <h4><?php echo $openTicket ?></h4>
                    <p class="text-muted mb-0">Tiket Open</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-stat p-4">
                    <i class="bi bi-hourglass-split mb-2"></i>
                    <h4><?php echo $processTicket ?></h4>
                    <p class="text-muted mb-0">In Progress</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-stat p-4">
                    <i class="bi bi-check-circle mb-2"></i>
                    <h4><?php echo $doneTicket ?></h4>
                    <p class="text-muted mb-0">Closed</p>
                </div>
            </div>
        </div>

    <!-- INFO -->
    <div class="card p-4 card-stat">
        <h5 class="mb-2">📢 Informasi</h5>
        <p class="text-muted mb-0">
            Gunakan menu di samping untuk membuat dan memantau tiket IT Anda.
            Pastikan data yang dikirim lengkap agar proses lebih cepat.
        </p>
    </div>
    <!-- INFORMASI & GLOSARIUM (ACCORDION) -->
<div class="card p-4 card-stat mt-4">
    <h5 class="mb-3">📘 Informasi & Glosarium IT Ticket</h5>

    <div class="accordion" id="accordionGlossary">

        <!-- APA IT TICKETING -->
        <div class="accordion-item border-0 mb-2">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed rounded-3"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#itTicketing">
                    🎫 Apa itu IT Ticketing System?
                </button>
            </h2>
            <div id="itTicketing" class="accordion-collapse collapse"
                 data-bs-parent="#accordionGlossary">
                <div class="accordion-body text-muted">
                    IT Ticketing System adalah sistem pencatatan dan pengelolaan
                    laporan atau permintaan layanan IT yang dibuat oleh pengguna
                    agar setiap permasalahan dapat ditangani secara terstruktur,
                    terdokumentasi, dan terpantau dengan baik.
                </div>
            </div>
        </div>

        <!-- STATUS TIKET -->
        <div class="accordion-item border-0 mb-2">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed rounded-3"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#statusTicket">
                    📊 Status Tiket
                </button>
            </h2>
            <div id="statusTicket" class="accordion-collapse collapse"
                 data-bs-parent="#accordionGlossary">
                <div class="accordion-body text-muted">
                    <ul class="mb-0">
                        <li><strong>Open</strong> — Tiket baru yang menunggu penanganan IT Support.</li>
                        <li><strong>In Progress</strong> — Tiket sedang dianalisis atau diperbaiki.</li>
                        <li><strong>Closed</strong> — Tiket telah selesai ditangani.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- KATEGORI MASALAH -->
        <div class="accordion-item border-0 mb-2">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed rounded-3"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#categoryTicket">
                    🧩 Kategori Permasalahan IT
                </button>
            </h2>
            <div id="categoryTicket" class="accordion-collapse collapse"
                 data-bs-parent="#accordionGlossary">
                <div class="accordion-body text-muted">
                    <ul class="mb-0">
                        <li><strong>Hardware</strong> — Masalah perangkat fisik (PC, printer, dll).</li>
                        <li><strong>Software</strong> — Masalah aplikasi atau sistem operasi.</li>
                        <li><strong>Network</strong> — Kendala jaringan atau koneksi internet.</li>
                        <li><strong>Email</strong> — Masalah akun email perusahaan.</li>
                        <li><strong>Lainnya</strong> — Permintaan akses atau kebutuhan IT lainnya.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- TIPS PENGGUNA -->
        <div class="accordion-item border-0">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed rounded-3"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#tipsTicket">
                    💡 Tips Membuat Tiket yang Baik
                </button>
            </h2>
            <div id="tipsTicket" class="accordion-collapse collapse"
                 data-bs-parent="#accordionGlossary">
                <div class="accordion-body text-muted">
                    Pastikan deskripsi tiket ditulis secara jelas dan lengkap,
                    sertakan informasi pendukung seperti pesan error, lokasi,
                    dan jenis perangkat agar proses penanganan dapat dilakukan
                    lebih cepat dan tepat.
                </div>
            </div>
        </div>

    </div>
</div>


</div>
<script>
document.getElementById('messageDropdown')
    .addEventListener('show.bs.dropdown', function () {

        fetch('mark_read.php', { method: 'POST' });

        const badge = document.getElementById('messageBadge');
        if (badge) badge.remove();
    });
</script>

</body>
</html>
