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
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Modify the addCredits handling:
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addCredits'])) {
    $additionalCredits = intval($_POST['additionalCredits']);
    $playerId = $playerDetails['regNo'];

    if ($additionalCredits > 0) {
        try {
            $updateCredits = $conn->prepare("UPDATE players SET credits = credits + ? WHERE regNo = ?");
            $updateCredits->bind_param("is", $additionalCredits, $playerId);

            if ($updateCredits->execute()) {
                $_SESSION['success_message'] = "Successfully added {$additionalCredits} credits!";
                
                if (isset($_SESSION['user_data']) && $_SESSION['user_data']['regNo'] == $playerId) {
                    $_SESSION['user_data']['credits'] += $additionalCredits;
                }
                
                header("Location: " . $_SERVER['PHP_SELF'] . "?" . $uniqueId);
                exit();
            } else {
                $_SESSION['error_message'] = "Error adding credits: " . $updateCredits->error;
                header("Location: " . $_SERVER['PHP_SELF'] . "?" . $uniqueId);
                exit();
            }
            $updateCredits->close();
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error updating credits: " . $e->getMessage();
            header("Location: " . $_SERVER['PHP_SELF'] . "?" . $uniqueId);
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Please enter a valid number of credits greater than 0";
        header("Location: " . $_SERVER['PHP_SELF'] . "?" . $uniqueId);
        exit();
    }
}

// Modify the replay event handling:
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['replayEventId'])) {
    $eventId = $_POST['replayEventId'];
    $playerId = $playerDetails['regNo'];

    // Fetch event details to get credits
    $eventStmt = $conn->prepare("SELECT credits FROM events WHERE id = ? AND playerRegno = ?");
    $eventStmt->bind_param("is", $eventId, $playerId);
    $eventStmt->execute();
    $eventResult = $eventStmt->get_result();
    $event = $eventResult->fetch_assoc();
    $eventStmt->close();

    if ($event) {
        $eventCredits = $event['credits'];

        if ($playerDetails['credits'] >= $eventCredits) {
            $conn->begin_transaction();

            try {
                $updateCredits = $conn->prepare("UPDATE players SET credits = credits - ? WHERE regNo = ?");
                $updateCredits->bind_param("is", $eventCredits, $playerId);
                $updateCredits->execute();

                $updateEvent = $conn->prepare("UPDATE events SET played = 0 WHERE id = ? AND playerRegno = ?");
                $updateEvent->bind_param("is", $eventId, $playerId);
                $updateEvent->execute();

                if (isset($_SESSION['user_data']) && $_SESSION['user_data']['regNo'] == $playerId) {
                    $_SESSION['user_data']['credits'] -= $eventCredits;
                }

                $conn->commit();
                $_SESSION['success_message'] = "Event replayed successfully! Credits deducted.";
                
                header("Location: " . $_SERVER['PHP_SELF'] . "?" . $uniqueId);
                exit();

            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error_message'] = "Error replaying event: " . $e->getMessage();
                header("Location: " . $_SERVER['PHP_SELF'] . "?" . $uniqueId);
                exit();
            }
        } else {
            $_SESSION['error_message'] = "Insufficient credits to replay this event!";
            header("Location: " . $_SERVER['PHP_SELF'] . "?" . $uniqueId);
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Event not found!";
        header("Location: " . $_SERVER['PHP_SELF'] . "?" . $uniqueId);
        exit();
    }
}

// Modify the mark as played handling:
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eventId'])) {
    $eventId = $_POST['eventId'];
    $playerId = $playerDetails['regNo'];

    $conn->begin_transaction();

    try {
        $checkStmt = $conn->prepare("SELECT played, ever_played FROM events WHERE id = ? AND playerRegno = ?");
        $checkStmt->bind_param("is", $eventId, $playerId);
        $checkStmt->execute();
        $eventResult = $checkStmt->get_result();
        $eventData = $eventResult->fetch_assoc();
        $checkStmt->close();

        $updateEvent = $conn->prepare("UPDATE events SET played = 1, ever_played = 1 WHERE id = ? AND playerRegno = ?");
        $updateEvent->bind_param("is", $eventId, $playerId);
        $updateEvent->execute();

        if (!$eventData['ever_played']) {
            $updatePlayer = $conn->prepare("UPDATE players SET eventsPlayed = eventsPlayed + 1 WHERE regNo = ?");
            $updatePlayer->bind_param("s", $playerId);
            $updatePlayer->execute();
        }

        $conn->commit();
        $_SESSION['success_message'] = "Event marked as played successfully!";
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?" . $uniqueId);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error updating event status: " . $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF'] . "?" . $uniqueId);
        exit();
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
        /* General styles for the dashboard container */
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Styles for the player information section */
        .player-info {
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        /* Styles for the credit management form */
        .credit-form {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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

        /* Styles for the events table */
        .events-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .events-table th, .events-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        .events-table th {
            background-color: #f4f4f4;
        }

        /* Status badge styles */
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
        }

        .status-played {
            background-color: #4CAF50;
            color: white;
        }

        .status-not-played {
            background-color: #f44336;
            color: white;
        }

        /* Action button styles */
        .action-button {
            padding: 6px 12px;
            background-color: #2196F3;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .action-button:hover {
            background-color: #1976D2;
        }

        .action-button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        /* Popup styles */
        .popup {
            visibility: hidden;
            min-width: 250px;
            margin-left: -125px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 5px;
            padding: 16px;
            position: fixed;
            z-index: 1;
            left: 50%;
            bottom: 30px;
            font-size: 17px;
            opacity: 0;
            transition: opacity 0.5s, bottom 0.5s;
        }

        .popup.show {
            visibility: visible;
            opacity: 1;
            bottom: 50px;
        }

        .popup.success {
            background-color: green;
        }

        .popup.error {
            background-color: red;
        }
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

            <!-- Credit Management Form -->
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
                                    <?php if ($event['played']): ?>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="replayEventId" value="<?php echo $event['id']; ?>">
                                            <button type="submit" class="action-button">Replay</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="eventId" value="<?php echo $event['id']; ?>">
                                            <button type="submit" class="action-button">Mark as Played</button>
                                        </form>
                                    <?php endif; ?>
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
