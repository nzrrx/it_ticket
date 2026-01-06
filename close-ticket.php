<?php
session_start();
include '../includes/db.php';

if ($_SESSION['role'] !== 'admin') exit;

$id = (int) $_GET['id'];

$stmt = $conn->prepare("
    UPDATE tickets SET status='Closed' WHERE id=?
");
$stmt->bind_param("i",$id);
$stmt->execute();

header("Location: tickets.php");
exit;
