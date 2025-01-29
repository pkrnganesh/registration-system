<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_data = $_SESSION['user_data'];
$error = '';
$success = '';

// Get user's registered events
$regNo = $user_data['regNo'];
$registeredEvents = [];
$stmt = $conn->prepare("SELECT eventName FROM events WHERE playerRegno = ?");
$stmt->bind_param("s", $regNo);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $registeredEvents[] = $row['eventName'];
}
$stmt->close();

// Define events and their details
$events = [
    'Free Fire' => ['credits' => 100, 'description' => 'Battle Royale Game'],
    'Squid' => ['credits' => 150, 'description' => 'Survival Game Challenge']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventName = $_POST['eventName'];
    $credits = $user_data['credits'];

    // Check if already registered
    if (in_array($eventName, $registeredEvents)) {
        $error = "You are already registered for $eventName!";
    } 
    // Check if enough credits
    else if ($credits >= $events[$eventName]['credits']) {
        // Deduct credits from the player
        $newCredits = $credits - $events[$eventName]['credits'];
        $updateCredits = $conn->prepare("UPDATE players SET credits = ? WHERE regNo = ?");
        $updateCredits->bind_param("is", $newCredits, $regNo);
        $updateCredits->execute();

        // Set default values
        $eventCreditsValue = $events[$eventName]['credits'];
        $score = 0;
        $played = 1;

        // Insert the event registration
        $insertEvent = $conn->prepare("INSERT INTO events (eventName, playerRegno, credits, score, played) VALUES (?, ?, ?, ?, ?)");
        $insertEvent->bind_param("ssiii", $eventName, $regNo, $eventCreditsValue, $score, $played);

        if ($insertEvent->execute()) {
            $success = "Successfully registered for $eventName!";
            // Update session data
            $user_data['credits'] = $newCredits;
            $_SESSION['user_data'] = $user_data;
            // Add to registered events array
            $registeredEvents[] = $eventName;
        } else {
            $error = "Error registering for the event: " . $insertEvent->error;
        }

        $insertEvent->close();
        $updateCredits->close();
    } else {
        $error = "Insufficient credits to register for $eventName!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fest Registration</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
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
        .events-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .events-table th, .events-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .events-table th {
            background-color: #f4f4f4;
        }
        .btn-register {
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-register:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        .status-registered {
            color: #4CAF50;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Welcome to the Fest</h1>
    
    <!-- User Information Section -->
    <h2>User Information</h2>
    <p><strong>Name:</strong> <?php echo htmlspecialchars($user_data['name']); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($user_data['email']); ?></p>
    <p><strong>Registration Number:</strong> <?php echo htmlspecialchars($user_data['regNo']); ?></p>
    <p><strong>Branch:</strong> <?php echo htmlspecialchars($user_data['branch']); ?></p>
    <p><strong>Year:</strong> <?php echo htmlspecialchars($user_data['year']); ?></p>
    <p><strong>Credits:</strong> <?php echo htmlspecialchars($user_data['credits']); ?></p>
    <p><strong>Events Played:</strong> <?php echo htmlspecialchars($user_data['eventsPlayed']); ?></p>
    <p><strong>Unique ID:</strong> <?php echo htmlspecialchars($user_data['uniqueId']); ?></p>

    <!-- Events Registration Table -->
    <h2>Available Events</h2>
    <table class="events-table">
        <thead>
            <tr>
                <th>Event Name</th>
                <th>Description</th>
                <th>Required Credits</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($events as $eventName => $eventDetails): ?>
            <tr>
                <td><?php echo htmlspecialchars($eventName); ?></td>
                <td><?php echo htmlspecialchars($eventDetails['description']); ?></td>
                <td><?php echo htmlspecialchars($eventDetails['credits']); ?></td>
                <td>
                    <?php if (in_array($eventName, $registeredEvents)): ?>
                        <span class="status-registered">Registered</span>
                    <?php else: ?>
                        <?php echo ($user_data['credits'] >= $eventDetails['credits']) ? 'Available' : 'Insufficient Credits'; ?>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="eventName" value="<?php echo htmlspecialchars($eventName); ?>">
                        <button type="submit" class="btn-register" 
                                <?php echo (in_array($eventName, $registeredEvents) || $user_data['credits'] < $eventDetails['credits']) ? 'disabled' : ''; ?>>
                            <?php echo in_array($eventName, $registeredEvents) ? 'Registered' : 'Register'; ?>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($success): ?>
        <div id="popup" class="popup success show"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div id="popup" class="popup error show"><?php echo $error; ?></div>
    <?php endif; ?>

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