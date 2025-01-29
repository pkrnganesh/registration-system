<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
session_regenerate_id(true);

$user_data = $_SESSION['user_data'];
$error = '';
$success = '';

// Get user's registered events
$regNo = $user_data['regNo'];
$registeredEvents = getRegisteredEvents($conn, $regNo);

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
        updatePlayerCredits($conn, $regNo, $newCredits);

        // Set default values
        $eventCreditsValue = $events[$eventName]['credits'];
        $score = 0;
        $played = 0; // Set played to 0

        // Insert the event registration
        if (registerForEvent($conn, $eventName, $regNo, $eventCreditsValue, $score, $played)) {
            $success = "Successfully registered for $eventName!";
            // Update session data
            $user_data['credits'] = $newCredits;
            $_SESSION['user_data'] = $user_data;
            // Add to registered events array
            $registeredEvents[] = $eventName;
        } else {
            $error = "Error registering for the event.";
        }
    } else {
        $error = "Insufficient credits to register for $eventName!";
    }
}

function getRegisteredEvents($conn, $regNo) {
    $stmt = $conn->prepare("SELECT eventName FROM events WHERE playerRegno = ?");
    $stmt->bind_param("s", $regNo);
    $stmt->execute();
    $result = $stmt->get_result();
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row['eventName'];
    }
    return $events;
}

function updatePlayerCredits($conn, $regNo, $newCredits) {
    $updateCredits = $conn->prepare("UPDATE players SET credits = ? WHERE regNo = ?");
    $updateCredits->bind_param("is", $newCredits, $regNo);
    $updateCredits->execute();
    $updateCredits->close();
}

function registerForEvent($conn, $eventName, $regNo, $eventCreditsValue, $score, $played) {
    $insertEvent = $conn->prepare("INSERT INTO events (eventName, playerRegno, credits, score, played) VALUES (?, ?, ?, ?, ?)");
    $insertEvent->bind_param("ssiii", $eventName, $regNo, $eventCreditsValue, $score, $played);
    return $insertEvent->execute();
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
        .qr-container {
            margin-top: 20px;
            text-align: center;
        }
        #qrCodeImage {
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            background: #f9f9f9;
        }
    </style>
</head>
<body>
    <h1>Welcome to the Fest</h1>

    <!-- User Information Section -->
    <h2>User Information</h2>
    <p><strong>Name:</strong> <span id="userName"><?php echo htmlspecialchars($user_data['name']); ?></span></p>
    <p><strong>Email:</strong> <span id="userEmail"><?php echo htmlspecialchars($user_data['email']); ?></span></p>
    <p><strong>Registration Number:</strong> <span id="userRegNo"><?php echo htmlspecialchars($user_data['regNo']); ?></span></p>
    <p><strong>Branch:</strong> <span id="userBranch"><?php echo htmlspecialchars($user_data['branch']); ?></span></p>
    <p><strong>Year:</strong> <span id="userYear"><?php echo htmlspecialchars($user_data['year']); ?></span></p>
    <p><strong>Credits:</strong> <span id="userCredits"><?php echo htmlspecialchars($user_data['credits']); ?></span></p>
    <p><strong>Events Played:</strong> <span id="userEventsPlayed"><?php echo htmlspecialchars($user_data['eventsPlayed']); ?></span></p>
    <p><strong>Unique ID:</strong> <span id="userUniqueId"><?php echo htmlspecialchars($user_data['uniqueId']); ?></span></p>

    <!-- QR Code Section -->
    <div class="qr-container">
        <h2>Your Check-In QR Code</h2>
        <img id="qrCodeImage" src="" alt="QR Code">
    </div>

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
        <tbody id="eventsTableBody">
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

    <!-- Include QRCode.js Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode/1.5.1/qrcode.min.js"></script>
    <script>
        // Generate QR Code for the user's unique ID
        const uniqueId = "<?php echo $user_data['uniqueId']; ?>";
        const playerDashboardUrl = `http://localhost/registration-system/admin/index.php?${uniqueId}`;

        QRCode.toDataURL(playerDashboardUrl, { errorCorrectionLevel: "H" }, (err, url) => {
            if (err) {
                console.error("Error generating QR code:", err);
                return;
            }
            // Set the generated QR code as the image source
            document.getElementById("qrCodeImage").src = url;
        });

        // Hide popup after 3 seconds
        window.onload = function() {
            const popup = document.getElementById('popup');
            if (popup) {
                setTimeout(function() {
                    popup.classList.remove('show');
                }, 3000);
            }
        };

        // Function to fetch user data and update the page
        function fetchUserData() {
    fetch('fetch_user_data.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update all user data fields
                document.getElementById('userName').textContent = data.user_data.name;
                document.getElementById('userEmail').textContent = data.user_data.email;
                document.getElementById('userRegNo').textContent = data.user_data.regNo;
                document.getElementById('userBranch').textContent = data.user_data.branch;
                document.getElementById('userYear').textContent = data.user_data.year;
                document.getElementById('userCredits').textContent = data.user_data.credits;
                document.getElementById('userEventsPlayed').textContent = data.user_data.eventsPlayed;
                document.getElementById('userUniqueId').textContent = data.user_data.uniqueId;

                // Update events table
                const eventsTableBody = document.getElementById('eventsTableBody');
                if (eventsTableBody) {
                    eventsTableBody.innerHTML = '';
                    data.events.forEach(event => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${event.eventName}</td>
                            <td>${event.description}</td>
                            <td>${event.credits}</td>
                            <td>${event.status}</td>
                            <td>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="eventName" value="${event.eventName}">
                                    <button type="submit" class="btn-register" ${event.disabled ? 'disabled' : ''}>
                                        ${event.buttonText}
                                    </button>
                                </form>
                            </td>
                        `;
                        eventsTableBody.appendChild(row);
                    });
                }
            }
        })
        .catch(error => console.error('Error fetching user data:', error));
}
        // Fetch user data every 5 seconds
        setInterval(fetchUserData, 5000);
    </script>
</body>
</html>
