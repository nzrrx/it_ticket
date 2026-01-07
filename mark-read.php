<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

/*
 Tandai SEMUA pesan milik user sebagai read
 (kecuali pesan yang dikirim oleh user sendiri)
*/
$stmt = $conn->prepare("
    UPDATE ticket_replies m
    JOIN tickets t ON m.ticket_id = t.id
    SET m.is_read = 1
    WHERE t.user_id = ?
      AND m.user_id != ?
      AND m.is_read = 0
");

$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();

echo 'OK';
