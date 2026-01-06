<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$ticket_id   = (int) $_POST['ticket_id'];
$status      = $_POST['status'];
$assigned_to = $_POST['assigned_to'] ?: NULL;

$stmt = $conn->prepare("
    UPDATE tickets 
    SET status=?, assigned_to=? 
    WHERE id=?
");

$stmt->bind_param("sii", $status, $assigned_to, $ticket_id);
$stmt->execute();

header("Location: ticket-detail.php?id=$ticket_id");
exit;
