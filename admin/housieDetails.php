<?php
session_start();
include '../includes/db.php';

function getOnlineHousiePlayers($conn) {
    $stmt = $conn->prepare("
        SELECT p.*, e.played, e.ever_played, e.play_count
        FROM players p
        INNER JOIN events e ON p.regNo = e.playerRegno
        WHERE e.eventName = 'Online Housie'
        ORDER BY p.regNo ASC
    ");
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get Online Housie players
$housiePlayers = getOnlineHousiePlayers($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Housie Players</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .players-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .players-table th, .players-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .players-table th {
            background-color: #f4f4f4;
        }
    </style>
</head>
<body>
    <h1>Online Housie Players</h1>
    
    <table class="players-table">
        <thead>
            <tr>
                <th>Registration Number</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone Number</th>
                <th>Branch</th>
                <th>Year</th>
                <th>Credits</th>
                <th>Played Status</th>
                <th>Play Count</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($housiePlayers as $player): ?>
                <tr>
                    <td><?php echo htmlspecialchars($player['regNo']); ?></td>
                    <td><?php echo htmlspecialchars($player['name']); ?></td>
                    <td><?php echo htmlspecialchars($player['email']); ?></td>
                    <td><?php echo htmlspecialchars($player['phoneNumber']); ?></td>
                    <td><?php echo htmlspecialchars($player['branch']); ?></td>
                    <td><?php echo htmlspecialchars($player['year']); ?></td>
                    <td><?php echo htmlspecialchars($player['credits']); ?></td>
                    <td><?php echo $player['played'] ? 'Yes' : 'No'; ?></td>
                    <td><?php echo htmlspecialchars($player['play_count']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>