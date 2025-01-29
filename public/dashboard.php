<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_data = $_SESSION['user_data'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fest Registration</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <h1>Welcome to the Fest</h1>
    <p>Register now and participate in exciting games!</p>
    <a href="register.php">Register Now</a>

    <h2>User Information</h2>
    <p><strong>Name:</strong> <?php echo htmlspecialchars($user_data['name']); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($user_data['email']); ?></p>
    <p><strong>Registration Number:</strong> <?php echo htmlspecialchars($user_data['regNo']); ?></p>
    <p><strong>Branch:</strong> <?php echo htmlspecialchars($user_data['branch']); ?></p>
    <p><strong>Year:</strong> <?php echo htmlspecialchars($user_data['year']); ?></p>
    <p><strong>Credits:</strong> <?php echo htmlspecialchars($user_data['credits']); ?></p>
    <p><strong>Events Played:</strong> <?php echo htmlspecialchars($user_data['eventsPlayed']); ?></p>
    <p><strong>Unique ID:</strong> <?php echo htmlspecialchars($user_data['uniqueId']); ?></p>
</body>
</html>
