<?php
include '../includes/db.php';

// Fetch user details based on user_id passed in the URL
$user_id = $_GET['user_id'] ?? 1; // Default user for testing
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

if (!$user) {
    die("User not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
</head>
<body>
    <h2>Welcome, <?= $user['name'] ?></h2>
    <p><strong>Email:</strong> <?= $user['email'] ?></p>
    <p><strong>Credits:</strong> <?= $user['credits'] ?></p>

    <h3>Your QR Code:</h3>
    <p>Use this QR code for event check-ins.</p>
    <img src="../scripts/generate_qr.php?user_id=<?= $user['id'] ?>" alt="QR Code for Check-in" />

    <h3>Transaction History:</h3>
    <table border="1">
        <tr>
            <th>Transaction ID</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Date</th>
        </tr>
        <?php
        // Fetch user's transaction history
        $transactions = $conn->query("SELECT * FROM payments WHERE user_id = $user_id");
        while ($transaction = $transactions->fetch_assoc()):
        ?>
        <tr>
            <td><?= $transaction['id'] ?></td>
            <td><?= $transaction['amount'] ?></td>
            <td><?= $transaction['payment_status'] ?></td>
            <td><?= $transaction['created_at'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
