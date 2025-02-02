<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$regNo = $_SESSION['user_data']['regNo'];

// Get fresh user data
$stmt = $conn->prepare("SELECT * FROM players WHERE regNo = ?");
$stmt->bind_param("s", $regNo);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

$_SESSION['user_data'] = $user_data;

$registeredEvents = getRegisteredEvents($conn, $regNo);

$events = [
    'Free Fire' => ['credits' => 100, 'description' => 'Battle Royale Game'],
    'Squid' => ['credits' => 150, 'description' => 'Survival Game Challenge']
];

$eventsData = [];
foreach ($events as $eventName => $eventDetails) {
    $status = in_array($eventName, $registeredEvents) ? 'Registered' :
              ($user_data['credits'] >= $eventDetails['credits'] ? 'Available' : 'Insufficient Credits');
    $disabled = in_array($eventName, $registeredEvents) || $user_data['credits'] < $eventDetails['credits'];
    $buttonText = in_array($eventName, $registeredEvents) ? 'Registered' : 'Register';

    $eventsData[] = [
        'eventName' => $eventName,
        'description' => $eventDetails['description'],
        'credits' => $eventDetails['credits'],
        'status' => $status,
        'disabled' => $disabled,
        'buttonText' => $buttonText
    ];
}
//set the dead line to end the registations
$currentDate = new DateTime();
$deadlineDate = new DateTime('2025-02-11');
$registrationOpen = $currentDate < $deadlineDate;

echo json_encode([
    'success' => true,
    'user_data' => $user_data,
    'events' => $eventsData,
    'registrationOpen' => $registrationOpen
]);

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

function updateUserSession($conn, $regNo) {
    $stmt = $conn->prepare("SELECT * FROM players WHERE regNo = ?");
    $stmt->bind_param("s", $regNo);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $_SESSION['user_data'] = $row;
    }
    $stmt->close();
}
?>
