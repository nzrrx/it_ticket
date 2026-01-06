<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit;
}

$ticket_id = (int) $_POST['ticket_id'];
$message   = trim($_POST['message']);

if ($message === '') {
    header("Location: ticket_detail.php?id=".$ticket_id);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO ticket_replies (ticket_id, sender_role, message)
    VALUES (?, 'user', ?)
");
$stmt->bind_param("is", $ticket_id, $message);
$stmt->execute();

header("Location: ticket_detail.php?id=".$ticket_id);
exit;
