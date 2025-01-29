<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    // Store the complete URL with parameters for redirect after login
    $_SESSION['redirect_url'] = 'index.php?' . $_SERVER['QUERY_STRING'];
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';
$playerDetails = [];
$registeredEvents = [];

// Get uniqueId from URL
if (isset($_GET)) {
    $keys = array_keys($_GET);
    $uniqueId = isset($keys[0]) ? $keys[0] : null;

    if ($uniqueId) {
        // Fetch player details
        $stmt = $conn->prepare("SELECT * FROM players WHERE uniqueId = ?");
        $stmt->bind_param("s", $uniqueId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $playerDetails = $result->fetch_assoc();
            
            // Fetch registered events
            $eventStmt = $conn->prepare("SELECT * FROM events WHERE playerRegno = ?");
            $eventStmt->bind_param("s", $playerDetails['regNo']);
            $eventStmt->execute();
            $eventResult = $eventStmt->get_result();

            while ($row = $eventResult->fetch_assoc()) {
                $registeredEvents[] = $row;
            }
            $eventStmt->close();
        }
        $stmt->close();
    }
}

// Handle adding credits
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addCredits'])) {
    $additionalCredits = intval($_POST['additionalCredits']);
    $playerId = $playerDetails['regNo'];

    if ($additionalCredits > 0) {
        try {
            // Update player credits
            $updateCredits = $conn->prepare("UPDATE players SET credits = credits + ? WHERE regNo = ?");
            $updateCredits->bind_param("is", $additionalCredits, $playerId);
            
            if ($updateCredits->execute()) {
                $success = "Successfully added {$additionalCredits} credits!";
                
                // Refresh player details
                $stmt = $conn->prepare("SELECT * FROM players WHERE uniqueId = ?");
                $stmt->bind_param("s", $uniqueId);
                $stmt->execute();
                $playerDetails = $stmt->get_result()->fetch_assoc();
            } else {
                $error = "Error adding credits: " . $updateCredits->error;
            }
            $updateCredits->close();
        } catch (Exception $e) {
            $error = "Error updating credits: " . $e->getMessage();
        }
    } else {
        $error = "Please enter a valid number of credits greater than 0";
    }
}

// Handle marking event as played (existing code)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eventId'])) {
    $eventId = $_POST['eventId'];
    $playerId = $playerDetails['regNo'];

    $conn->begin_transaction();

    try {
        $updateEvent = $conn->prepare("UPDATE events SET played = 1 WHERE id = ? AND playerRegno = ?");
        $updateEvent->bind_param("is", $eventId, $playerId);
        $updateEvent->execute();

        $updatePlayer = $conn->prepare("UPDATE players SET eventsPlayed = eventsPlayed + 1 WHERE regNo = ?");
        $updatePlayer->bind_param("s", $playerId);
        $updatePlayer->execute();

        $conn->commit();
        $success = "Event marked as played successfully!";

        // Refresh data
        $stmt = $conn->prepare("SELECT * FROM players WHERE uniqueId = ?");
        $stmt->bind_param("s", $uniqueId);
        $stmt->execute();
        $playerDetails = $stmt->get_result()->fetch_assoc();

        $eventStmt = $conn->prepare("SELECT * FROM events WHERE playerRegno = ?");
        $eventStmt->bind_param("s", $playerDetails['regNo']);
        $eventStmt->execute();
        $eventResult = $eventStmt->get_result();
        $registeredEvents = [];
        while ($row = $eventResult->fetch_assoc()) {
            $registeredEvents[] = $row;
        }

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error updating event status: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        /* Previous styles remain the same */
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .player-info {
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .credit-form {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .credit-form input[type="number"] {
            padding: 8px;
            margin-right: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 150px;
        }
        .credit-form button {
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .credit-form button:hover {
            background-color: #45a049;
        }
        .current-credits {
            font-size: 1.2em;
            color: #2196F3;
            font-weight: bold;
        }
        /* Rest of the previous styles... */
    </style>
</head>
<body>
    <div class="dashboard-container">
        <h1>Admin Dashboard</h1>

        <?php if (!empty($playerDetails)): ?>
            <div class="player-info">
                <h2>Player Details</h2>
                <table>
                    <tr>
                        <td><strong>Name:</strong></td>
                        <td><?php echo htmlspecialchars($playerDetails['name']); ?></td>
                        <td><strong>Email:</strong></td>
                        <td><?php echo htmlspecialchars($playerDetails['email']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Registration Number:</strong></td>
                        <td><?php echo htmlspecialchars($playerDetails['regNo']); ?></td>
                        <td><strong>Branch:</strong></td>
                        <td><?php echo htmlspecialchars($playerDetails['branch']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Year:</strong></td>
                        <td><?php echo htmlspecialchars($playerDetails['year']); ?></td>
                        <td><strong>Credits:</strong></td>
                        <td class="current-credits"><?php echo htmlspecialchars($playerDetails['credits']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Events Played:</strong></td>
                        <td><?php echo htmlspecialchars($playerDetails['eventsPlayed']); ?></td>
                        <td><strong>Unique ID:</strong></td>
                        <td><?php echo htmlspecialchars($playerDetails['uniqueId']); ?></td>
                    </tr>
                </table>
            </div>

            <!-- New Credit Management Form -->
            <div class="credit-form">
                <h3>Add Credits</h3>
                <form method="POST" action="">
                    <input type="number" 
                           name="additionalCredits" 
                           placeholder="Enter credits" 
                           min="1" 
                           required>
                    <button type="submit" name="addCredits">Add Credits</button>
                </form>
            </div>

            <?php if (!empty($registeredEvents)): ?>
                <h2>Registered Events</h2>
                <table class="events-table">
                    <thead>
                        <tr>
                            <th>Event ID</th>
                            <th>Event Name</th>
                            <th>Credits</th>
                            <th>Score</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registeredEvents as $event): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($event['id']); ?></td>
                                <td><?php echo htmlspecialchars($event['eventName']); ?></td>
                                <td><?php echo htmlspecialchars($event['credits']); ?></td>
                                <td><?php echo htmlspecialchars($event['score']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $event['played'] ? 'status-played' : 'status-not-played'; ?>">
                                        <?php echo $event['played'] ? 'Played' : 'Not Played'; ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="eventId" value="<?php echo $event['id']; ?>">
                                        <button type="submit" 
                                                class="action-button" 
                                                <?php echo $event['played'] ? 'disabled' : ''; ?>>
                                            <?php echo $event['played'] ? 'Completed' : 'Mark as Played'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No registered events found for this player.</p>
            <?php endif; ?>
        <?php else: ?>
            <p>No player found with the provided Unique ID.</p>
        <?php endif; ?>

        <?php if ($success): ?>
            <div id="popup" class="popup success show"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div id="popup" class="popup error show"><?php echo $error; ?></div>
        <?php endif; ?>
    </div>

    <script>
        window.onload = function() {
            const popup = document.getElementById('popup');
            if (popup) {
                setTimeout(function() {
                    popup.classList.remove('show');
                }, 3000);
            }
        };
    </script>
</body>
</html>