<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    $_SESSION['redirect_url'] = 'index.php?' . $_SERVER['QUERY_STRING'];
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';
$playerDetails = [];
$registeredEvents = [];

if (isset($_GET)) {
    $keys = array_keys($_GET);
    $uniqueId = isset($keys[0]) ? $keys[0] : null;

    if ($uniqueId) {
        $stmt = $conn->prepare("SELECT * FROM players WHERE uniqueId = ?");
        $stmt->bind_param("s", $uniqueId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $playerDetails = $result->fetch_assoc();

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

if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['replayEventId'])) {
    $eventId = $_POST['replayEventId'];
    $playerId = $playerDetails['regNo'];

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eventId'])) {
    $eventId = $_POST['eventId'];
    $playerId = $playerDetails['regNo'];

    $conn->begin_transaction();

    try {
        $checkStmt = $conn->prepare("SELECT played, ever_played, play_count FROM events WHERE id = ? AND playerRegno = ?");
        $checkStmt->bind_param("is", $eventId, $playerId);
        $checkStmt->execute();
        $eventResult = $checkStmt->get_result();
        $eventData = $eventResult->fetch_assoc();
        $checkStmt->close();

        $updateEvent = $conn->prepare("UPDATE events SET played = 1, ever_played = 1, play_count = play_count + 1 WHERE id = ? AND playerRegno = ?");
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
        body {
            background-color: #000;
            color: #fff;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .dashboard-container {
            max-width: 400px;
            margin: 20px auto;
            padding: 20px;
            border-radius: 8px;
            background-color: #1e1e1e;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        h1, h2, h3 {
            text-align: center;
            margin-bottom: 20px;
        }

        .player-info, .credit-form, .events-table {
            margin-bottom: 20px;
        }

        .player-info p, .credit-form p {
            margin: 5px 0;
        }

        .player-info p strong {
            font-weight: bold;
        }

        .credit-form input[type="number"] {
            width: calc(100% - 22px);
            padding: 8px;
            margin-right: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #333;
            color: #fff;
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

        .events-table {
            width: 100%;
            border-collapse: collapse;
        }

        .events-table th, .events-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        .events-table th {
            background-color: #333;
        }

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
                <p><strong>Name:</strong> <?php echo htmlspecialchars($playerDetails['name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($playerDetails['email']); ?></p>
                <p><strong>Branch:</strong> <?php echo htmlspecialchars($playerDetails['branch']); ?></p>
                <p><strong>Unique ID:</strong> <?php echo htmlspecialchars($playerDetails['uniqueId']); ?></p>
                <p><strong>Credits:</strong> <?php echo htmlspecialchars($playerDetails['credits']); ?></p>
                <p><strong>Register Number:</strong> <?php echo htmlspecialchars($playerDetails['regNo']); ?></p>
            </div>

            <div class="credit-form">
                <h3>Add Credits</h3>
                <form method="POST" action="">
                    <input type="number" name="additionalCredits" placeholder="Enter credits" min="1" required>
                    <button type="submit" name="addCredits">Add Credits</button>
                </form>
            </div>

            <?php if (!empty($registeredEvents)): ?>
                <h2>Registered Events</h2>
                <table class="events-table">
                    <thead>
                        <tr>
                            <th>Event name</th>
                            <th>Credits</th>
                            <th>Status</th>
                            <th>Times played</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registeredEvents as $event): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($event['eventName']); ?></td>
                                <td><?php echo htmlspecialchars($event['credits']); ?></td>
                                <td><?php echo $event['played'] ? 'Played' : 'Not Played'; ?></td>
                                <td><?php echo htmlspecialchars($event['play_count']); ?></td>
                                <td>
                                    <?php if ($event['played']): ?>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="replayEventId" value="<?php echo $event['id']; ?>">
                                            <button type="submit" class="action-button">Reply</button>
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
