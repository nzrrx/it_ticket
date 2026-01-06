<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: tickets.php");
    exit;
}

$ticket_id = (int) $_POST['ticket_id'];
$message   = trim($_POST['message']);

if ($message === '') {
    header("Location: detail-ticket.php?id=".$ticket_id);
    exit;
}

/* SIMPAN BALASAN ADMIN */
$admin_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    INSERT INTO ticket_replies (ticket_id, user_id, message)
    VALUES (?, ?, ?)
");
$stmt->bind_param("iis", $ticket_id, $admin_id, $message);
$stmt->execute();


/* UPDATE STATUS OTOMATIS */
$update = $conn->prepare("
    UPDATE tickets 
    SET status='In Progress'
    WHERE id=? AND status='Open'
");
$update->bind_param("i", $ticket_id);
$update->execute();

/* REDIRECT */
header("Location: detail-ticket.php?id=".$ticket_id);
exit;
