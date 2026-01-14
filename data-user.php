<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$keyword = $_GET['keyword'] ?? '';

$stmt = $conn->prepare("
        SELECT 
    id,
    name,
    email,
    role,
    created_at
FROM users
ORDER BY role DESC;
");
$stmt->execute();
$tickets = $stmt->get_result();

$sql = "
    SELECT id, name, email, role, created_at
    FROM users
    WHERE 1=1
";

$params = [];
$types  = '';

if (!empty($keyword)) {
    $sql .= " AND name LIKE ? ";
    $params[] = "%$keyword%";
    $types .= "s";
}

$sql .= " ORDER BY role DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$tickets = $stmt->get_result();


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title> Data User | MICS IT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    background: rgba(255,255,255,.2);
}

.sidebar h4{font-weight:700}

.sidebar a:hover,.sidebar a.active{
    background:rgba(255,255,255,.2)
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
    <title>Data User</title>
</head>
<body>

    <!-- SIDEBAR -->
<div class="sidebar p-4">
<h4 class="mb-4">🛠️ MICS IT</h4>

<a href="ticket.php">
<i class="bi bi-speedometer2 me-2"></i> Dashboard
</a>

<a href="ticket-admin.php">
<i class="bi bi-ticket-detailed me-2"></i> Semua Tiket
</a>

<a href="data-user.php"  class="active">
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
            <i class="bi bi-ticket-perforated"></i> Data User
        </h4>

        <a href="user-add.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah User
        </a>
    </div>

    <div class="row mb-3">
    <div class="col-md-4">
        <label class="form-label fw-semibold">
            <i class="bi bi-search"></i> Cari Nama User
        </label>
        <input type="text"
               id="searchUser"
               class="form-control"
               placeholder="Ketik nama user...">
    </div>
</div>


    <!-- <form method="GET" class="row g-2 mb-3 align-items-end">

    <div class="col-md-4">
        <input type="text"
               name="keyword"
               class="form-control"
               placeholder="Cari nama user..."
               value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
    </div> -->


    <div class="card shadow-sm border-0">
        <div class="card-body">

            <?php if ($tickets->num_rows === 0): ?>
                <div class="alert alert-info text-center">
                    Data User Tidak Tersedia.
                </div>
            <?php else: ?>

            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Tanggal Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="userTable">
                <?php $no = 1; while ($row = $tickets->fetch_assoc()): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td>
                        <span class="badge bg-<?= $row['role']=='admin'?'danger':'primary' ?>">
                    <?= ucfirst($row['role']) ?>
                    </span>
                        </td>
                        <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                        <td>
                            <a href="edit-data-user.php?id=<?= $row['id'] ?>"
   class="btn btn-sm btn-outline-primary">
   <i class="bi bi-pencil-square"></i> Edit
</a>

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
const searchInput = document.getElementById('searchUser');
const tableBody   = document.getElementById('userTable');

let timeout = null;

searchInput.addEventListener('keyup', function () {
    clearTimeout(timeout);

    timeout = setTimeout(() => {
        const keyword = this.value;

        fetch('ajax-search-user.php?keyword=' + encodeURIComponent(keyword))
            .then(res => res.text())
            .then(html => {
                tableBody.innerHTML = html;
            });
    }, 300); // debounce 300ms
});
</script>

</body>
</html>