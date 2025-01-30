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

if(isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

$regNo = $user_data['regNo'];
$registeredEvents = getRegisteredEvents($conn, $regNo);

$events = [
    'Free Fire' => ['credits' => 100, 'description' => 'Battle Royale Game'],
    'Squid' => ['credits' => 150, 'description' => 'Survival Game Challenge']
];

// Check if an event name is passed via the URL
if (isset($_GET['event'])) {
    $eventName = ucwords(str_replace('_', ' ', $_GET['event']));
    if (array_key_exists($eventName, $events)) {
        $credits = $user_data['credits'];

        if (in_array($eventName, $registeredEvents)) {
            $error = "You are already registered for $eventName!";
        } else if ($credits >= $events[$eventName]['credits']) {
            $newCredits = $credits - $events[$eventName]['credits'];
            updatePlayerCredits($conn, $regNo, $newCredits);

            $eventCreditsValue = $events[$eventName]['credits'];
            $score = 0;
            $played = 0;

            if (registerForEvent($conn, $eventName, $regNo, $eventCreditsValue, $score, $played)) {
                $success = "Successfully registered for $eventName!";
                $user_data['credits'] = $newCredits;
                $_SESSION['user_data'] = $user_data;
                $registeredEvents[] = $eventName;
            } else {
                $error = "Error registering for the event.";
            }
        } else {
            $error = "Insufficient credits to register for $eventName!";
        }
    } else {
        $error = "Invalid event name!";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eventName'])) {
    $eventName = $_POST['eventName'];
    $credits = $user_data['credits'];

    if (in_array($eventName, $registeredEvents)) {
        $error = "You are already registered for $eventName!";
    } else if ($credits >= $events[$eventName]['credits']) {
        $newCredits = $credits - $events[$eventName]['credits'];
        updatePlayerCredits($conn, $regNo, $newCredits);

        $eventCreditsValue = $events[$eventName]['credits'];
        $score = 0;
        $played = 0;

        if (registerForEvent($conn, $eventName, $regNo, $eventCreditsValue, $score, $played)) {
            $success = "Successfully registered for $eventName!";
            $user_data['credits'] = $newCredits;
            $_SESSION['user_data'] = $user_data;
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
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            position: relative;
        }
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
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
            background: rgba(255,255,255,0.05);
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
            background: linear-gradient(45deg, #2196F3, #00BCD4);
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
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
            overflow: hidden;
        }
        .events-table th, .events-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .events-table th {
            background-color: rgba(255,255,255,0.1);
            font-weight: 500;
            text-transform: uppercase;
            font-size: 14px;
        }
        .events-table tbody tr:hover {
            background-color: rgba(255,255,255,0.05);
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
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
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
                font-size: 12px; /* Reduced base font size */
            }
            
            .events-table th, 
            .events-table td {
                padding: 8px 6px; /* Reduced padding */
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
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-section">
            <div class="profile-header">
                <div class="profile-avatar" onclick="toggleQRPopup()">
                    <?php echo strtoupper(substr($user_data['name'], 0, 1)); ?>
                </div>
                <div class="profile-name">
                    <h2><?php echo htmlspecialchars($user_data['name']); ?></h2>
                    <p><?php echo htmlspecialchars($user_data['email']); ?></p>
                </div>
            </div>
            <div class="profile-info">
                <div class="info-item">
                    <label>Registration Number</label>
                    <span><?php echo htmlspecialchars($user_data['regNo']); ?></span>
                </div>
                <div class="info-item">
                    <label>Available Credits</label>
                    <span><?php echo htmlspecialchars($user_data['credits']); ?></span>
                </div>
            </div>
        </div>

        <table class="events-table">
            <thead>
                <tr>
                    <th>Event Name</th>
                    <th>Required Credits</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $eventName => $eventDetails): ?>
                <tr>
                    <td><?php echo htmlspecialchars($eventName); ?></td>
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
    </div>

    <div class="qr-popup" id="qrPopup">
        <h3>Profile QR Code</h3>
        <p>Scan to add credits</p>
        <img id="qrCodeImage" src="" alt="QR Code">
        <p>ID: <?php echo htmlspecialchars($user_data['uniqueId']); ?></p>
        <form method="POST" action="">
            <button type="submit" name="logout" class="logout-btn">Logout</button>
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode/1.5.1/qrcode.min.js"></script>
    <script>
const uniqueId = "<?php echo $user_data['uniqueId']; ?>";
        const playerDashboardUrl = `http://localhost/registration-system/admin/index.php?${uniqueId}`;

        // Generate QR Code
        QRCode.toDataURL(playerDashboardUrl, { 
            errorCorrectionLevel: "H",
            width: 200,
            height: 200,
            margin: 1
        }, (err, url) => {
            if (err) {
                console.error("Error generating QR code:", err);
                return;
            }
            document.getElementById("qrCodeImage").src = url;
        });

        // Handle popup messages
        window.onload = function() {
            const popup = document.getElementById('popup');
            if (popup) {
                setTimeout(function() {
                    popup.classList.remove('show');
                }, 3000);
            }
        };

        function toggleQRPopup() {
    const qrPopup = document.getElementById('qrPopup');
    if (qrPopup.style.display === 'block') {
        qrPopup.style.display = 'none';
    } else {
        qrPopup.style.display = 'block';
    }
}

        // Close QR popup when clicking outside
        document.addEventListener('click', function(event) {
            const qrPopup = document.getElementById('qrPopup');
            const profileAvatar = document.querySelector('.profile-avatar');
            
            if (qrPopup.style.display === 'block' && 
                !qrPopup.contains(event.target) && 
                !profileAvatar.contains(event.target)) {
                qrPopup.style.display = 'none';
            }
        });

        // Fetch updated user data
        function fetchUserData() {
            fetch('fetch_user_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update profile information
                        document.querySelector('.profile-name h2').textContent = data.user_data.name;
                        document.querySelector('.profile-name p').textContent = data.user_data.email;
                        document.querySelector('.info-item span').textContent = data.user_data.regNo;
                        
                        // Update credits
                        const creditsSpan = document.querySelectorAll('.info-item span')[1];
                        if (creditsSpan) {
                            creditsSpan.textContent = data.user_data.credits;
                        }

                        // Update events table
                        if (data.events) {
                            const eventsTableBody = document.querySelector('.events-table tbody');
                            if (eventsTableBody) {
                                eventsTableBody.innerHTML = '';
                                data.events.forEach(event => {
                                    const row = document.createElement('tr');
                                    row.innerHTML = `
                                        <td>${event.eventName}</td>
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
                    }
                })
                .catch(error => console.error('Error fetching user data:', error));
        }

        // Update data every 5 seconds
        setInterval(fetchUserData, 5000);

        // Add smooth transitions for buttons
        document.querySelectorAll('.btn-register').forEach(button => {
            button.addEventListener('mouseover', function() {
                if (!this.disabled) {
                    this.style.transform = 'translateY(-2px)';
                }
            });
            
            button.addEventListener('mouseout', function() {
                if (!this.disabled) {
                    this.style.transform = 'translateY(0)';
                }
            });
        });
    </script>
</body>
</html>