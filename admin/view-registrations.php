<?php
include '../includes/db.php';
$result = $conn->query("SELECT * FROM users");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Registrations</title>
</head>
<body>
    <h2>Registered Users</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>College</th>
            <th>Year</th>
            <th>Credits</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['name'] ?></td>
            <td><?= $row['email'] ?></td>
            <td><?= $row['phone'] ?></td>
            <td><?= $row['college'] ?></td>
            <td><?= $row['year_of_study'] ?></td>
            <td><?= $row['credits'] ?></td>
            <td>
                <a href="approve.php?id=<?= $row['id'] ?>">Approve</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
