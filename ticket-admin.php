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
$keyword   = $_GET['keyword'] ?? '';
$status    = $_GET['status'] ?? '';
$fromMonth = $_GET['from_month'] ?? '';
$toMonth   = $_GET['to_month'] ?? '';

$sql = "
    SELECT 
        t.*,
        u.name AS user_name,
        u.email,
        a.name AS admin_name
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN users a ON t.assigned_to = a.id
    WHERE 1=1
";

$params = [];
$types  = '';

$userId = $_GET['user_id'] ?? '';

if ($userId) {
    $sql .= " AND t.user_id = ? ";
    $params[] = $userId;
    $types .= 'i';
}


// if ($keyword) {
//     $sql .= " AND u.name LIKE ? ";
//     $params[] = "%$keyword%";
//     $types .= 's';
// }

if ($status) {
    $sql .= " AND t.status = ? ";
    $params[] = $status;
    $types .= 's';
}

if ($fromMonth) {
    $sql .= " AND DATE_FORMAT(t.created_at, '%Y-%m') >= ? ";
    $params[] = $fromMonth;
    $types .= 's';
}

if ($toMonth) {
    $sql .= " AND DATE_FORMAT(t.created_at, '%Y-%m') <= ? ";
    $params[] = $toMonth;
    $types .= 's';
}

$sql .= "
    ORDER BY 
        CASE 
            WHEN t.status = 'In Progress' THEN 1
            WHEN t.status = 'Open' THEN 2
            WHEN t.status = 'Closed' THEN 3
            ELSE 4
        END,
        t.created_at DESC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$tickets = $stmt->get_result();

$userList = $conn->query("
    SELECT id, name
    FROM users
    ORDER BY name ASC
");

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


    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">


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

    <div class="content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">
                <i class="bi bi-ticket-perforated"></i> Semua Tiket
            </h4>

            <a href="add-ticket-admin.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Tambah Tiket
            </a>
        </div>

            
    <form method="GET" class="row g-3 mb-4 align-items-end">

        <!-- SEARCH USER -->
        <div class="col-md-3">
            <label class="form-label fw-semibold">
                <i class="bi bi-person"></i> Nama User
            </label>

            <select name="user_id" class="form-select select-user">
                <option value="">Semua User</option>


                <?php while ($u = $userList->fetch_assoc()): ?>
                    <option value="<?= $u['id'] ?>"
                        <?= (($_GET['user_id'] ?? '') == $u['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- <script>
$(document).ready(function () {
    $('.select-user').select2({
        placeholder: "Cari nama user...",
        allowClear: true,
        width: '100%'
    });
});
</script> -->


        <!-- STATUS -->
        <div class="col-md-2">
            <label class="form-label fw-semibold">
                <i class="bi bi-info-circle"></i> Status Tiket
            </label>
            <select name="status" class="form-select">
                <option value="">Semua Status</option>
                <option value="Open" <?= ($_GET['status'] ?? '') == 'Open' ? 'selected' : '' ?>>Open</option>
                <option value="In Progress" <?= ($_GET['status'] ?? '') == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="Closed" <?= ($_GET['status'] ?? '') == 'Closed' ? 'selected' : '' ?>>Closed</option>
            </select>
        </div>

        <!-- FROM MONTH -->
        <div class="col-md-2">
            <label class="form-label fw-semibold">
                <i class="bi bi-calendar-event"></i> Dari Bulan
            </label>
            <input type="month"
                name="from_month"
                class="form-control"
                value="<?= $_GET['from_month'] ?? '' ?>">
        </div>

        <!-- TO MONTH -->
        <div class="col-md-2">
            <label class="form-label fw-semibold">
                <i class="bi bi-calendar-check"></i> Sampai Bulan
            </label>
            <input type="month"
                name="to_month"
                class="form-control"
                value="<?= $_GET['to_month'] ?? '' ?>">
        </div>

        <!-- BUTTON -->
        <div class="col-md-3">
            <div class="d-flex gap-2">
                <button class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Filter
                </button>

                <?php if (!empty($_GET)) : ?>
                    <a href="ticket-admin.php"
                        class="btn btn-outline-secondary w-100">
                        <i class="bi bi-x-circle"></i> Reset
                    </a>
                <?php endif; ?>
            </div>
        </div>

    </form>

    <div class="d-flex gap-2 mb-3">
                <button class="btn btn-success"
                    onclick="exportExcel('all')">
                    <i class="bi bi-file-earmark-excel"></i> Export Semua
                </button>

                <button class="btn btn-outline-success"
                    onclick="exportExcel('filtered')">
                    Export Sesuai Filter
                </button>

                <button class="btn btn-outline-primary"
                    onclick="exportExcel('selected')">
                    Export Dipilih
                </button>
        </div>



    <div class="card shadow-sm border-0">
        <div class="card-body">

            <?php if ($tickets->num_rows === 0): ?>
                <div class="alert alert-info text-center">
                    Anda belum memiliki tiket.
                </div>
            <?php else: ?>

                <table class="table table-hover align-middle">
                    <thead class="table-light text-center">
                        <tr>
                            <th>
                                <input type="checkbox" id="checkAll">
                            </th>
                            <th>#</th>
                            <th>Nama</th>
                            <th>Judul</th>
                            <th>Kategori</th>
                            <th>Prioritas</th>
                            <th>Status</th>
                            <th>Dibuat</th>
                            <th>Di-Close Oleh</th>
                        </tr>
                    </thead>
                    <tbody class="table-light text-center">

                        <?php $no = 1;
                        while ($row = $tickets->fetch_assoc()): ?>
                            <tr onclick="window.location='detail-ticket.php?id=<?= $row['id'] ?>'"
                                style="cursor:pointer;">
                                <td onclick="event.stopPropagation()">
                                    <input type="checkbox"
                                        class="ticket-check"
                                        value="<?= $row['id'] ?>">
                                </td>

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
                                <td><?= date('d M Y H:i', strtotime($row['created_at'])) ?></td>
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

                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

            <?php endif; ?>
        </div>
    </div>
    </div>

    <script>
        document.getElementById('checkAll')?.addEventListener('change', function() {
            document.querySelectorAll('.ticket-check').forEach(cb => {
                cb.checked = this.checked;
            });
        });
    </script>

    <script>
        function exportExcel(mode) {

            let params = new URLSearchParams(window.location.search);

            if (mode === 'selected') {
                let ids = [];
                document.querySelectorAll('.ticket-check:checked').forEach(cb => {
                    ids.push(cb.value);
                });

                if (ids.length === 0) {
                    alert('Pilih minimal 1 tiket');
                    return;
                }

                params.append('ids', ids.join(','));
            }

            params.append('mode', mode);

            window.location.href = 'export-excel.php?' + params.toString();
        }
    </script>


</body>

</html>