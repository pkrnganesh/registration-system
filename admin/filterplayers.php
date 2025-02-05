<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player Filter</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .filter-form {
            margin-bottom: 20px;
        }

        .filter-form input, .filter-form select {
            margin: 5px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .filter-form button {
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .filter-form button:hover {
            background-color: #45a049;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .results-table th, .results-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        .results-table th {
            background-color: #f4f4f4;
        }
    </style>
</head>

<body>
    <h1>Player Filter</h1>

    <form class="filter-form" method="POST" action="">
        <label for="eventName">Select Event:</label>
        <select name="eventName" id="eventName">
            <option value="">All Events</option>
            <?php
            $events = ['Free Fire', 'Squid', 'Game1', 'Game2', 'Game3', 'Game4', 'Game5'];
            foreach ($events as $event) {
                echo "<option value='$event'>$event</option>";
            }
            ?>
        </select>

        <label for="regNo">Registration Number:</label>
        <input type="text" name="regNo" id="regNo" placeholder="Enter Registration Number">

        <label for="phoneNumber">Phone Number:</label>
        <input type="text" name="phoneNumber" id="phoneNumber" placeholder="Enter Phone Number">

        <label for="uniqueId">Unique ID:</label>
        <input type="text" name="uniqueId" id="uniqueId" placeholder="Enter Unique ID">

        <button type="submit" name="filter">Filter</button>
        <button type="submit" name="export" formaction="export.php" formmethod="POST">Export to Excel</button>
    </form>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter'])) {
        include '../includes/db.php';

        $eventName = $_POST['eventName'];
        $regNo = $_POST['regNo'];
        $phoneNumber = $_POST['phoneNumber'];
        $uniqueId = $_POST['uniqueId'];

        $query = "SELECT p.name, p.email, p.regNo, p.branch, p.year, p.credits, p.eventsPlayed, p.uniqueId, e.eventName, e.credits AS eventCredits, e.played, e.play_count
                  FROM players p
                  LEFT JOIN events e ON p.regNo = e.playerRegno";

        $conditions = [];
        $params = [];
        $types = '';

        if (!empty($eventName)) {
            $conditions[] = "e.eventName = ?";
            $params[] = $eventName;
            $types .= 's';
        }

        if (!empty($regNo)) {
            $conditions[] = "p.regNo = ?";
            $params[] = $regNo;
            $types .= 's';
        }

        if (!empty($phoneNumber)) {
            $conditions[] = "p.phoneNumber = ?";
            $params[] = $phoneNumber;
            $types .= 's';
        }

        if (!empty($uniqueId)) {
            $conditions[] = "p.uniqueId = ?";
            $params[] = $uniqueId;
            $types .= 's';
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<table class='results-table'>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Registration Number</th>
                            <th>Branch</th>
                            <th>Year</th>
                            <th>Credits</th>
                            <th>Events Played</th>
                            <th>Unique ID</th>
                            <th>Event Name</th>
                            <th>Event Credits</th>
                            <th>Played</th>
                            <th>Play Count</th>
                        </tr>
                    </thead>
                    <tbody>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . htmlspecialchars($row['name']) . "</td>
                        <td>" . htmlspecialchars($row['email']) . "</td>
                        <td>" . htmlspecialchars($row['regNo']) . "</td>
                        <td>" . htmlspecialchars($row['branch']) . "</td>
                        <td>" . htmlspecialchars($row['year']) . "</td>
                        <td>" . htmlspecialchars($row['credits']) . "</td>
                        <td>" . htmlspecialchars($row['eventsPlayed']) . "</td>
                        <td>" . htmlspecialchars($row['uniqueId']) . "</td>
                        <td>" . htmlspecialchars($row['eventName']) . "</td>
                        <td>" . htmlspecialchars($row['eventCredits']) . "</td>
                        <td>" . ($row['played'] ? 'Yes' : 'No') . "</td>
                        <td>" . htmlspecialchars($row['play_count']) . "</td>
                      </tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<p>No results found.</p>";
        }
    }
    ?>
</body>

</html>
