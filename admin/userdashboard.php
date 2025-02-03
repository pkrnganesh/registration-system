<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
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
        } else {
            $error = "Player not found.";
        }
        $stmt->close();
    } else {
        $error = "No ID entered.";
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registerEvent'])) {
    $eventName = $_POST['eventName'];
    $credits = intval($_POST['credits']);
    $playerId = $playerDetails['regNo'];

    if ($playerDetails['credits'] >= $credits) {
        $conn->begin_transaction();

        try {
            // Update player credits
            $updateCredits = $conn->prepare("UPDATE players SET credits = credits - ? WHERE regNo = ?");
            $updateCredits->bind_param("is", $credits, $playerId);
            $updateCredits->execute();

            // Insert event with correct number of parameters
            $insertEvent = $conn->prepare("INSERT INTO events (eventName, credits, playerRegno) VALUES (?, ?, ?)");
            $insertEvent->bind_param("sis", $eventName, $credits, $playerId);
            $insertEvent->execute();

            if (isset($_SESSION['user_data']) && $_SESSION['user_data']['regNo'] == $playerId) {
                $_SESSION['user_data']['credits'] -= $credits;
            }

            $conn->commit();
            $_SESSION['success_message'] = "Successfully registered for {$eventName}! Credits deducted.";

            header("Location: " . $_SERVER['PHP_SELF'] . "?" . $uniqueId);
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Error registering for event: " . $e->getMessage();
            header("Location: " . $_SERVER['PHP_SELF'] . "?" . $uniqueId);
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Insufficient credits to register for this event!";
        header("Location: " . $_SERVER['PHP_SELF'] . "?" . $uniqueId);
        exit();
    }
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
$events = [
    'Squid' => 150,
    'Code Fighters' => 100,
    'Red Light Green Light' => 120,
    'Gongi' => 50,
    'Dalgona Cookie' => 10,
    'Dadkji' => 500,
    'Temple Run' => 500,
    'Code Master' => 500,
    'Spell Casters' => 500,
    'KBC' => 500,
    'Ideathon' => 500,
    'Online Housie' => 500,
    'Clash Battle' => 500
];

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
            font-family: Arial, sans-serif;
            background-color: #000;
            color: #fff;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .profile-section {
            background: linear-gradient(145deg, #1a1a1a, #333);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .profile-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, #ff4b2b, #ff416c);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-right: 15px;
            color: white;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .profile-avatar:hover {
            transform: scale(1.05);
        }

        .profile-name {
            flex-grow: 1;
        }

        .profile-name h2 {
            margin: 0;
            font-size: 24px;
            color: #fff;
        }

        .profile-name p {
            margin: 5px 0 0;
            color: #888;
            font-size: 14px;
        }

        .profile-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 8px;
        }

        .info-item label {
            display: block;
            color: #888;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .info-item span {
            color: #fff;
            font-size: 16px;
            font-weight: 500;
        }

        .credit-display {
            background: linear-gradient(45deg, #ff4b2b, rgb(2, 2, 2));
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-top: 20px;
        }

        .credit-display .number {
            font-size: 36px;
            font-weight: bold;
            color: white;
        }

        .events-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: hidden;
        }

        .events-table th,
        .events-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .events-table th {
            background-color: rgba(255, 255, 255, 0.1);
            font-weight: 500;
            text-transform: uppercase;
            font-size: 14px;
        }

        .events-table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .btn-register {
            padding: 8px 16px;
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: transform 0.2s;
        }

        .btn-register:hover:not(:disabled) {
            transform: translateY(-2px);
        }

        .btn-register:disabled {
            background: #666;
            cursor: not-allowed;
        }

        .status-registered {
            color: #4CAF50;
            font-weight: 500;
        }

        .qr-popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #1a1a1a;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
            z-index: 1000;
            text-align: center;
        }

        .qr-popup img {
            margin: 15px 0;
            padding: 15px;
            background: white;
            border-radius: 10px;
            max-width: 200px;
        }

        .logout-btn {
            background: #ff416c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 15px;
            font-weight: 500;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: #ff4b2b;
        }

        /* Update the base popup styles */
        .popup {
            visibility: hidden;
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 14px;
            opacity: 0;
            transition: all 0.3s;
            z-index: 1000;
            text-align: center;
            min-width: 200px;
            max-width: 90%;
        }

        .popup.show {
            visibility: visible;
            opacity: 1;
            bottom: 40px;
        }

        .popup.success {
            background-color: #4CAF50;
            color: white;
            box-shadow: 0 2px 10px rgba(76, 175, 80, 0.3);
        }

        .popup.error {
            background-color: #f44336;
            color: white;
            box-shadow: 0 2px 10px rgba(244, 67, 54, 0.3);
        }

        /* Add mobile-specific styles */
        @media (max-width: 600px) {
            .popup {
                padding: 8px 16px;
                font-size: 12px;
                min-width: 150px;
                max-width: 85%;
                bottom: 20px;
            }

            .popup.show {
                bottom: 30px;
            }
        }

        /* Extra small screens */
        @media (max-width: 360px) {
            .popup {
                padding: 6px 12px;
                font-size: 11px;
                min-width: 120px;
                border-radius: 4px;
            }
        }

        /* Add these styles inside your existing media query for mobile devices */
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }

            .profile-info {
                grid-template-columns: 1fr;
            }

            .profile-name h2 {
                font-size: 20px;
            }

            .credit-display .number {
                font-size: 28px;
            }

            /* Updated table styles for mobile */
            .events-table {
                font-size: 12px;
                /* Reduced base font size */
            }

            .events-table th,
            .events-table td {
                padding: 8px 6px;
                /* Reduced padding */
                font-size: 12px;
                text-align: center;
            }

            .events-table th {
                font-size: 11px;
                text-transform: uppercase;
                font-weight: 600;
            }

            /* Make the buttons smaller on mobile */
            .btn-register {
                padding: 6px 10px;
                font-size: 11px;
            }

            /* Adjust column widths for better mobile display */
            .events-table td:first-child {
                font-size: 12px;
                font-weight: 500;
            }

            .events-table td:nth-child(2) {
                font-size: 11px;
            }

            .events-table td:nth-child(3) {
                font-size: 11px;
            }

            .status-registered {
                font-size: 11px;
            }

            /* Add horizontal scroll for very small screens */
            .events-table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }

        /* Add styles for extra small screens */
        @media (max-width: 360px) {

            .events-table th,
            .events-table td {
                padding: 6px 4px;
                font-size: 11px;
            }

            .btn-register {
                padding: 4px 8px;
                font-size: 10px;
            }
        }

        /* Back button styles */
        .back-btn {
            background: #ff416c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s;
            margin-top: 20px;
            display: inline-block;
        }

        .back-btn:hover {
            background: #ff4b2b;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php if ($error): ?>
            <div id="popup" class="popup error show"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div id="popup" class="popup success show"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (!empty($playerDetails)): ?>
            <div class="profile-section">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo substr($playerDetails['name'], 0, 1); ?>
                    </div>
                    <div class="profile-name">
                        <h2><?php echo htmlspecialchars($playerDetails['name']); ?></h2>
                        <p><?php echo htmlspecialchars($playerDetails['email']); ?></p>
                    </div>
                </div>
                <div class="profile-info">
                    <div class="info-item">
                        <label>Branch</label>
                        <span><?php echo htmlspecialchars($playerDetails['branch']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Unique ID</label>
                        <span><?php echo htmlspecialchars($playerDetails['uniqueId']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Credits</label>
                        <span><?php echo htmlspecialchars($playerDetails['credits']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Register Number</label>
                        <span><?php echo htmlspecialchars($playerDetails['regNo']); ?></span>
                    </div>
                </div>
                <div class="credit-display">
                    <div class="number"><?php echo htmlspecialchars($playerDetails['credits']); ?></div>
                    <form method="POST" action="">
                        <input type="number" name="additionalCredits" placeholder="Enter credits" min="1" required>
                        <button type="submit" name="addCredits" class="btn-register">Add Credits</button>
                    </form>
                </div>
            </div>

            <?php if (!empty($registeredEvents)): ?>
                <div class="events-table-container">
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
                                                <button type="submit" class="btn-register">Reply</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="eventId" value="<?php echo $event['id']; ?>">
                                                <button type="submit" class="btn-register">Mark as Played</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No registered events found for this player.</p>
            <?php endif; ?>

            <!-- All Events Table -->
        <!-- All Events Table -->
<h3>All Available Events</h3>
<div class="events-table-container">
    <table class="events-table">
        <thead>
            <tr>
                <th>Event name</th>
                <th>Credits</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Create an array of registered event names for easy checking
            $registeredEventNames = array_map(function($event) {
                return $event['eventName'];
            }, $registeredEvents);
            
            foreach ($events as $eventName => $credits): 
                $isRegistered = in_array($eventName, $registeredEventNames);
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($eventName); ?></td>
                    <td><?php echo htmlspecialchars($credits); ?></td>
                    <td>
                        <?php if ($isRegistered): ?>
                            <button type="submit" class="btn-register" disabled>Registered</button>
                        <?php else: ?>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="eventName" value="<?php echo $eventName; ?>">
                                <input type="hidden" name="credits" value="<?php echo $credits; ?>">
                                <button type="submit" name="registerEvent" class="btn-register">Register</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
        <?php endif; ?>

        <!-- Back Button -->
        <a href="index.php" class="back-btn">Back</a>
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