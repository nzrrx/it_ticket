
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | IT Ticketing</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

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
        <span class="badge bg-primary">Online</span>
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

</div>

</body>
</html>
