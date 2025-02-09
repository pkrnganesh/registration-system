<?php
include '../includes/db.php';

// Fixed query with properly qualified column names
$query = "SELECT p.id, p.name, p.regNo, p.credits as player_base_credits,
          GROUP_CONCAT(DISTINCT e.eventName) as events,
          SUM(CASE
              WHEN e.play_count > 0 AND e.played = 0 THEN (e.credits * e.play_count) + e.credits
              WHEN e.play_count > 0 AND e.played = 1 THEN e.credits * e.play_count
              ELSE e.credits
          END) as event_credits
          FROM players p
          LEFT JOIN events e ON p.regNo = e.playerRegno
          GROUP BY p.id, p.name, p.regNo, p.credits";

$result = $conn->query($query);

// Fixed stats query with properly qualified column names
$statsQuery = "SELECT
    COUNT(DISTINCT p.id) as total_players,
    SUM(p.credits + COALESCE(e_credits.total_event_credits, 0)) as total_credits
FROM players p
LEFT JOIN (
    SELECT playerRegno,
    SUM(CASE
        WHEN play_count > 0 AND played = 0 THEN (credits * play_count) + credits
        WHEN play_count > 0 AND played = 1 THEN credits * play_count
        ELSE credits
    END) as total_event_credits
    FROM events
    GROUP BY playerRegno
) e_credits ON p.regNo = e_credits.playerRegno";

$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();

// Fixed event details query
$eventDetailsQuery = "SELECT playerRegno, eventName, credits,
                     CASE
                         WHEN play_count > 0 AND played = 0 THEN (credits * play_count) + credits
                         WHEN play_count > 0 AND played = 1 THEN credits * play_count
                         ELSE credits
                     END as calculated_credits
                     FROM events
                     ORDER BY playerRegno, eventName";
$eventDetails = $conn->query($eventDetailsQuery);

// Store event details by player
$playerEvents = [];
while ($event = $eventDetails->fetch_assoc()) {
    $playerEvents[$event['playerRegno']][] = $event;
}

// Handle Excel Export
if(isset($_POST['export'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="player_credits.xls"');
    header('Cache-Control: max-age=0');

    // Output Excel file content
    echo "ID\tName\tRegistration Number\tBase Credits\tEvent Credits\tTotal Credits\tEvents Details\n";

    $result->data_seek(0); // Reset pointer to beginning
    while($row = $result->fetch_assoc()) {
        $events_details = '';
        if (isset($playerEvents[$row['regNo']])) {
            foreach ($playerEvents[$row['regNo']] as $event) {
                $events_details .= $event['eventName'] . ' (' . $event['calculated_credits'] . ' credits), ';
            }
            $events_details = rtrim($events_details, ', ');
        }

        echo $row['id'] . "\t" .
             $row['name'] . "\t" .
             $row['regNo'] . "\t" .
             ($row['player_base_credits'] ?? 0) . "\t" .
             ($row['event_credits'] ?? 0) . "\t" .
             (($row['player_base_credits'] ?? 0) + ($row['event_credits'] ?? 0)) . "\t" .
             $events_details . "\n";
    }
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player Credits Summary</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .summary-box {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        .export-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .export-btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="summary-box">
        <h2>Summary Statistics</h2>
        <p>Total Players: <?php echo htmlspecialchars($stats['total_players']); ?></p>
        <p>Total Credits Across All Players: <?php echo htmlspecialchars($stats['total_credits']); ?></p>
    </div>

    <form method="post">
        <button type="submit" name="export" class="export-btn">Export to Excel</button>
    </form>

    <h1>Player Credits Summary</h1>

    <?php if ($result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Registration Number</th>
                    <th>Base Credits</th>
                    <th>Event Credits</th>
                    <th>Total Credits</th>
                    <th>Event Details</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['regNo']); ?></td>
                        <td><?php echo htmlspecialchars($row['player_base_credits'] ?? 0); ?></td>
                        <td><?php echo htmlspecialchars($row['event_credits'] ?? 0); ?></td>
                        <td><?php echo htmlspecialchars(($row['player_base_credits'] ?? 0) + ($row['event_credits'] ?? 0)); ?></td>
                        <td>
                            <?php if (isset($playerEvents[$row['regNo']])): ?>
                                <?php foreach ($playerEvents[$row['regNo']] as $event): ?>
                                    <?php echo htmlspecialchars($event['eventName']); ?>
                                    (<?php echo htmlspecialchars($event['calculated_credits']); ?> credits)<br>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No players found.</p>
    <?php endif; ?>
</body>
</html>
