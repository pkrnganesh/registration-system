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

$regNo = $user_data['regNo'];
$registeredEvents = getRegisteredEvents($conn, $regNo);

$events = [
    'Free Fire' => ['credits' => 100, 'description' => 'Battle Royale Game'],
    'Squid' => ['credits' => 150, 'description' => 'Survival Game Challenge']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .container {
            text-align: center;
            width: 90%;
            max-width: 400px;
        }
        .user-info {
            margin-bottom: 20px;
        }
        .user-info p {
            margin: 5px 0;
        }
        .credit-circle {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 20px auto;
        }
        .credit-circle .circle {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 150px;
            height: 150px;
            margin-top: -75px;
            margin-left: -75px;
            border-radius: 50%;
            border: 10px solid #fff;
            box-sizing: border-box;
        }
        .credit-circle .number {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 48px;
            color: #fff;
        }
        .qr-container {
            margin-bottom: 20px;
        }
        .qr-container img {
            max-width: 100%;
            height: auto;
        }
        .events-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .events-table th, .events-table td {
            border: 1px solid #fff;
            padding: 10px;
            text-align: center;
        }
        .events-table th {
            background-color: #333;
        }
        .btn-register {
            padding: 5px 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-register:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        .status-registered {
            color: #4CAF50;
            font-weight: bold;
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
        @media (max-width: 600px) {
            .container {
                width: 95%;
            }
            .credit-circle {
                width: 100px;
                height: 100px;
            }
            .credit-circle .circle {
                width: 100px;
                height: 100px;
                margin-top: -50px;
                margin-left: -50px;
            }
            .credit-circle .number {
                font-size: 32px;
            }
            .events-table th, .events-table td {
                padding: 8px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="qr-container">
            <p>credits</p>
        </div>
        <div class="credit-circle">
            <div class="circle"></div>
            <div class="number"><?php echo htmlspecialchars($user_data['credits']); ?></div>
        </div>
        <div class="qr-container">
            <p>QR code for checkin</p>
            <img id="qrCodeImage" src="" alt="QR Code">
            <p>Unique ID: <?php echo htmlspecialchars($user_data['uniqueId']); ?></p>
        </div>
        <table class="events-table">
            <thead>
                <tr>
                    <th>Event name</th>
                    <th>Required Credits</th>
                    <th>Status</th>
                    <th>Register</th>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode/1.5.1/qrcode.min.js"></script>
    <script>
        const uniqueId = "<?php echo $user_data['uniqueId']; ?>";
        const playerDashboardUrl = `http://localhost/registration-system/admin/index.php?${uniqueId}`;

        QRCode.toDataURL(playerDashboardUrl, { errorCorrectionLevel: "H" }, (err, url) => {
            if (err) {
                console.error("Error generating QR code:", err);
                return;
            }
            document.getElementById("qrCodeImage").src = url;
        });

        window.onload = function() {
            const popup = document.getElementById('popup');
            if (popup) {
                setTimeout(function() {
                    popup.classList.remove('show');
                }, 3000);
            }
        };

        function fetchUserData() {
            fetch('fetch_user_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector('.user-info p:nth-child(1) strong').textContent = data.user_data.name;
                        document.querySelector('.user-info p:nth-child(2)').textContent = data.user_data.email;
                        document.querySelector('.user-info p:nth-child(3)').textContent = 'Registration Number: ' + data.user_data.regNo;
                        document.querySelector('.user-info p:nth-child(4)').textContent = 'Events Played: ' + data.user_data.eventsPlayed;
                        document.querySelector('.credit-circle .number').textContent = data.user_data.credits;

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
                })
                .catch(error => console.error('Error fetching user data:', error));
        }

        setInterval(fetchUserData, 5000);
    </script>
</body>
</html>
