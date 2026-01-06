<?php
// Helper functions for stats, validation, etc.
require_once 'db.php';

// Validate and sanitize input
function sanitize($data) {
    return htmlspecialchars(trim($data));
}

// Check if user is logged in and has role
function checkRole($requiredRole) {
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== $requiredRole) {
        header('Location: login.php');
        exit();
    }
}

// Calculate average reply time (hours)
function getAverageReplyTime() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT AVG(TIMESTAMPDIFF(HOUR, t.created_at, r.created_at)) AS avg_time
        FROM tickets t
        JOIN ticket_replies r ON t.id = r.ticket_id
        WHERE r.id = (SELECT MIN(id) FROM ticket_replies WHERE ticket_id = t.id)
    ");
    return round($stmt->fetch()['avg_time'] ?? 0, 2);
}

// Get customer satisfaction (average rating)
function getCustomerSatisfaction() {
    global $pdo;
    $stmt = $pdo->query("SELECT AVG(rating) AS avg_rating FROM ticket_ratings");
    return round($stmt->fetch()['avg_rating'] ?? 0, 1);
}

// Get tickets solved by agent
function getTicketsByAgent() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT u.name, COUNT(t.id) AS count
        FROM users u
        LEFT JOIN tickets t ON u.id = t.assigned_to AND t.status = 'Solved'
        WHERE u.role = 'admin'
        GROUP BY u.id
    ");
    return $stmt->fetchAll();
}
?>