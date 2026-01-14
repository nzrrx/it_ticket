<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    exit('Unauthorized');
}

/* =========================
   EXCEL HEADER
========================= */
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=tickets_" . date('Ymd_His') . ".xls");

$mode = $_GET['mode'] ?? 'all';

/* =========================
   BASE QUERY
========================= */
$sql = "
    SELECT 
        t.id,
        u.name AS user_name,
        u.email,
        t.subject,
        t.category,
        t.priority,
        t.status,
        t.description,
        a.name AS assigned_to,
        t.created_at,
        t.updated_at
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN users a ON t.assigned_to = a.id
    WHERE 1=1
";

$params = [];
$types  = "";

/* =========================
   MODE: SELECTED
========================= */
if ($mode === 'selected' && !empty($_GET['ids'])) {
    $ids = array_map('intval', explode(',', $_GET['ids']));
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $sql .= " AND t.id IN ($in) ";
    $types .= str_repeat('i', count($ids));
    $params = array_merge($params, $ids);
}

/* =========================
   MODE: FILTERED
========================= */
if ($mode === 'filtered') {

    if (!empty($_GET['user_id'])) {
        $sql .= " AND t.user_id = ? ";
        $types .= 'i';
        $params[] = $_GET['user_id'];
    }

    if (!empty($_GET['status'])) {
        $sql .= " AND t.status = ? ";
        $types .= 's';
        $params[] = $_GET['status'];
    }

    if (!empty($_GET['from_month'])) {
        $sql .= " AND DATE_FORMAT(t.created_at, '%Y-%m') >= ? ";
        $types .= 's';
        $params[] = $_GET['from_month'];
    }

    if (!empty($_GET['to_month'])) {
        $sql .= " AND DATE_FORMAT(t.created_at, '%Y-%m') <= ? ";
        $types .= 's';
        $params[] = $_GET['to_month'];
    }
}

$sql .= " ORDER BY t.created_at DESC ";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

/* =========================
   OUTPUT TABLE
========================= */
echo "<table border='1'>
<tr style='font-weight:bold; background:#f2f2f2'>
    <th>ID</th>
    <th>Nama User</th>
    <th>Email</th>
    <th>Judul</th>
    <th>Deskripsi</th>
    <th>Kategori</th>
    <th>Prioritas</th>
    <th>Status</th>
    <th>Dibuat</th>
    <th>Diclose Oleh</th>
    <th>Diupdate</th>
</tr>";

while ($row = $result->fetch_assoc()) {

    echo "<tr>
        <td>{$row['id']}</td>
        <td>{$row['user_name']}</td>
        <td>{$row['email']}</td>
        <td>{$row['subject']}</td>
        <td>{$row['description']}</td>
        <td>{$row['category']}</td>
        <td>{$row['priority']}</td>
        <td>{$row['status']}</td>
        <td>{$row['created_at']}</td>
        <td>" . ($row['assigned_to'] ?? '-') . "</td>
        <td>{$row['updated_at']}</td>
    </tr>";
}

echo "</table>";
exit;
