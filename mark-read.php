<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    http_response_code(401);
    exit;
}

if (!isset($_POST['message_id'])) {
    http_response_code(400);
    exit;
}

$message_id = (int) $_POST['message_id'];
//$user_id = (int) $_SESSION['user_id'];

// /*
//  Tandai SEMUA pesan milik user sebagai read
//  (kecuali pesan yang dikirim oleh user sendiri)
// */
// $stmt = $conn->prepare("
//     UPDATE ticket_replies m
//     JOIN tickets t ON m.ticket_id = t.id
//     SET m.is_read = 1
//     WHERE t.user_id = ?
//       AND m.user_id != ?
//       AND m.is_read = 0
// ");

// $stmt->bind_param("ii", $user_id, $user_id);
// $stmt->execute();

// echo 'OK';



$stmt = $conn->prepare("
    UPDATE ticket_replies
    SET is_read = 1
    WHERE id = ?
");
$stmt->bind_param("i", $message_id);
$stmt->execute();

echo 'OK';

