<?php
include '../includes/db.php';
$user_id = $_GET['user_id'] ?? 0;

// Fetch user details
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
    <title>Payment</title>
</head>
<body>
    <h2>Payment for Registration</h2>
    <p>Name: <?= $user['name'] ?></p>
    <p>Email: <?= $user['email'] ?></p>
    <p>Credits: <?= $user['credits'] ?></p>

    <form action="../scripts/process_payment.php" method="POST">
        <input type="hidden" name="user_id" value="<?= $user_id ?>">
        <label for="amount">Amount:</label>
        <input type="number" name="amount" id="amount" required>
        <button type="submit">Pay</button>
    </form>
</body>
</html>
