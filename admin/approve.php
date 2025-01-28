<?php
include '../includes/db.php';
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $conn->query("UPDATE users SET credits = 100 WHERE id = $id"); // Give 100 credits
    header("Location: view-registrations.php");
}
?>
