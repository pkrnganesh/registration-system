<?php
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $amount = $_POST['amount'];

    // Insert Payment Record
    $stmt = $conn->prepare("INSERT INTO payments (user_id, amount, payment_status) VALUES (?, ?, 'SUCCESS')");
    $stmt->bind_param("id", $user_id, $amount);
    
    if ($stmt->execute()) {
        // Update User Credits
        $conn->query("UPDATE users SET credits = credits + $amount WHERE id = $user_id");
        echo "Payment successful!";
    } else {
        echo "Payment failed!";
    }
}
?>
