<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    exit;
}

if (!isset($_POST['message_id'])) {
    http_response_code(400);
    exit;
}

$message_id = (int) $_POST['message_id'];

$stmt = $conn->prepare("
    UPDATE ticket_replies
    SET is_read = 1
    WHERE id = ?
");
$stmt->bind_param("i", $message_id);
$stmt->execute();

echo 'OK';
