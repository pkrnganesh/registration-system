<?php include '../includes/db.php'; ?>
<?php include '../includes/header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
</head>
<body>
    <h2>Register for the Fest</h2>
    <form action="../scripts/process_registration.php" method="POST">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>

        <label for="phone">Phone:</label>
        <input type="text" id="phone" name="phone" required>

        <label for="college">College:</label>
        <input type="text" id="college" name="college" required>

        <label for="year">Year of Study:</label>
        <input type="number" id="year" name="year" required>

        <button type="submit">Submit</button>
    </form>
</body>
</html>

<?php include '../includes/footer.php'; ?>
