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

$players = getAllPlayers($conn);
$events = [
    'Free Fire', 
    'Gonggi', 
    '30 Tiles', 
    'Puck Board Sling', 
    'Dalgona Cookie Game', 
    'Squid Hunt', 
    'Red Light Green Light', 
    'Code Masters', 
    'Spell Bee', 
    'Ideathon', 
    'Online Housie',
    'Code Fighters'
];
$selectedEvent = isset($_GET['event']) ? $_GET['event'] : '';
$players = $selectedEvent ? getPlayersByEvent($conn, $selectedEvent) : getAllPlayers($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addCredits'])) {
        $regNo = $_POST['regNo'];
        $creditsToAdd = $_POST['creditsToAdd'];
        addCredits($conn, $regNo, $creditsToAdd);
        $success = "Credits added successfully!";
        $players = $selectedEvent ? getPlayersByEvent($conn, $selectedEvent) : getAllPlayers($conn);
    } elseif (isset($_POST['playEvent'])) {
        $regNo = $_POST['regNo'];
        $eventName = $_POST['eventName'];
        playEvent($conn, $eventName, $regNo);
        $success = "Event played successfully!";
        $players = $selectedEvent ? getPlayersByEvent($conn, $selectedEvent) : getAllPlayers($conn);
    } elseif (isset($_POST['replayEvent'])) {
        $regNo = $_POST['regNo'];
        $eventName = $_POST['eventName'];
        $eventCredits = getEventCredits($eventName);
        $playerCredits = getPlayerCredits($conn, $regNo);
        if ($playerCredits >= $eventCredits) {
            replayEvent($conn, $eventName, $regNo, $eventCredits);
            $success = "Event set for replay successfully!";
            $players = $selectedEvent ? getPlayersByEvent($conn, $selectedEvent) : getAllPlayers($conn);
        } else {
            $error = "Not enough credits to replay this event!";
        }
    } elseif (isset($_POST['deletePlayer'])) {
        $regNo = $_POST['regNo'];
        $eventName = $_POST['eventName'];
        deletePlayerEvent($conn, $eventName, $regNo);
        $success = "Player entry deleted successfully!";
        $players = $selectedEvent ? getPlayersByEvent($conn, $selectedEvent) : getAllPlayers($conn);
    }
}

function getAllPlayers($conn)
{
    $stmt = $conn->prepare("SELECT * FROM players");
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getPlayerCredits($conn, $regNo)
{
    $stmt = $conn->prepare("SELECT credits FROM players WHERE regNo = ?");
    $stmt->bind_param("s", $regNo);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['credits'];
}

function addCredits($conn, $regNo, $creditsToAdd)
{
    $stmt = $conn->prepare("UPDATE players SET credits = credits + ? WHERE regNo = ?");
    $stmt->bind_param("is", $creditsToAdd, $regNo);
    $stmt->execute();
}

function registerForEvent($conn, $eventName, $regNo, $eventCredits)
{
    $stmt = $conn->prepare("INSERT INTO events (eventName, playerRegno, credits, score, played, ever_played, play_count) VALUES (?, ?, ?, 0, 0, 0, 0)");
    $stmt->bind_param("ssi", $eventName, $regNo, $eventCredits);
    $stmt->execute();
}

function playEvent($conn, $eventName, $regNo)
{
    $stmt = $conn->prepare("UPDATE events SET played = 1, ever_played = 1 WHERE eventName = ? AND playerRegno = ?");
    $stmt->bind_param("ss", $eventName, $regNo);
    $stmt->execute();
}

function getEventCredits($eventName)
{
    $events = [
        'Free Fire' => 160,
        'Gonggi' => 10,
        '30 Tiles' => 20,
        'Puck Board Sling' => 30,
        'Dalgona Cookie Game' => 30,
        'Squid Hunt' => 50,
        'Red Light Green Light' => 30,
        'Code Masters' => 30,
        'Spell Bee' => 30,
        'Ideathon' => 10,
        'Online Housie' => 30,
        'Code Fighters' => 30
    ];
    return $events[$eventName];
}

function deductCreditsForEvent($conn, $eventName, $regNo)
{
    $eventCredits = getEventCredits($eventName);
    $stmt = $conn->prepare("UPDATE players SET credits = credits - ? WHERE regNo = ?");
    $stmt->bind_param("is", $eventCredits, $regNo);
    $stmt->execute();
}

function getPlayerEventStatus($conn, $regNo)
{
    $stmt = $conn->prepare("SELECT eventName, played, ever_played, play_count FROM events WHERE playerRegno = ?");
    $stmt->bind_param("s", $regNo);
    $stmt->execute();
    $result = $stmt->get_result();
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[$row['eventName']] = [
            'played' => $row['played'],
            'ever_played' => $row['ever_played'],
            'play_count' => $row['play_count']
        ];
    }
    return $events;
}

function replayEvent($conn, $eventName, $regNo, $eventCredits)
{
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE events SET played = 0, play_count = play_count + 1 WHERE eventName = ? AND playerRegno = ?");
        $stmt->bind_param("ss", $eventName, $regNo);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE players SET credits = credits - ? WHERE regNo = ?");
        $stmt->bind_param("is", $eventCredits, $regNo);
        $stmt->execute();

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function getPlayersByEvent($conn, $eventName) {
    $stmt = $conn->prepare("
        SELECT DISTINCT p.*, e.played, e.ever_played, e.play_count
        FROM players p
        JOIN events e ON p.regNo = e.playerRegno
        WHERE e.eventName = ?
    ");
    $stmt->bind_param("s", $eventName);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function deletePlayerEvent($conn, $eventName, $regNo)
{
    $stmt = $conn->prepare("DELETE FROM events WHERE eventName = ? AND playerRegno = ?");
    $stmt->bind_param("ss", $eventName, $regNo);
    $stmt->execute();
}

function exportPlayersToCSV($players, $eventName)
{
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="players.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Name', 'Email', 'Registration Number', 'Event Name', 'Total Credits', 'Played']);

    foreach ($players as $player) {
        fputcsv($output, [
            $player['name'],
            $player['email'],
            $player['regNo'],
            $eventName,
            $player['credits'],
            $player['played'] ? 'Yes' : 'No'
        ]);
    }

    fclose($output);
    exit();
}

if (isset($_GET['export'])) {
    exportPlayersToCSV($players, $selectedEvent);
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

        .search-bar {
            margin-bottom: 20px;
        }

        .players-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .players-table th,
        .players-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        .players-table th {
            background-color: #f4f4f4;
        }

        .btn {
            padding: 8px 16px;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 2px;
        }

        .btn-register {
            background-color: #4CAF50;
        }

        .btn-play {
            background-color: #2196F3;
        }

        .btn-played {
            background-color: #808080;
        }

        .btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        .event-column {
            min-width: 120px;
        }

        .btn-replay {
            background-color: #ff9800;
        }

        .btn-replay:disabled {
            background-color: #cccccc;
        }

        .event-column {
            min-width: 200px;
        }

        .btn-delete {
            background-color: #f44336;
        }

        .btn-export {
            background-color: #4CAF50;
            margin-left: 10px;
        }

        .btn-export:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
    </style>
</head>

<body>
    <h1>Admin Dashboard</h1>

    <div class="search-bar">
        <input type="text" id="searchInput" placeholder="Search by Registration Number" onkeyup="filterPlayers()">
        <select id="eventFilter" onchange="filterByEvent(this.value)">
            <option value="">Select Event</option>
            <?php foreach ($events as $eventName): ?>
                <option value="<?php echo htmlspecialchars($eventName); ?>"
                    <?php echo $selectedEvent === $eventName ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($eventName); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($selectedEvent && !empty($players)): ?>
            <button class="btn btn-export" onclick="exportPlayers()">Export</button>
        <?php else: ?>
            <button class="btn btn-export" disabled>Export</button>
        <?php endif; ?>
    </div>

    <table class="players-table" id="playersTable">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Registration Number</th>
                <th>Branch</th>
                <th>Year</th>
                <th>Credits</th>
                <th>Add Credits</th>
                <?php if ($selectedEvent): ?>
                    <th><?php echo htmlspecialchars($selectedEvent); ?></th>
                <?php endif; ?>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($players as $player): ?>
                <tr>
                    <td><?php echo htmlspecialchars($player['name']); ?></td>
                    <td><?php echo htmlspecialchars($player['email']); ?></td>
                    <td><?php echo htmlspecialchars($player['regNo']); ?></td>
                    <td><?php echo htmlspecialchars($player['branch']); ?></td>
                    <td><?php echo htmlspecialchars($player['year']); ?></td>
                    <td><?php echo htmlspecialchars($player['credits']); ?></td>
                    <td>
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="regNo" value="<?php echo htmlspecialchars($player['regNo']); ?>">
                            <input type="number" name="creditsToAdd" placeholder="Credits" required>
                            <button type="submit" name="addCredits" class="btn btn-register">Add</button>
                        </form>
                    </td>
                    <?php if ($selectedEvent): ?>
                        <td>
                            <?php if ($player['played'] == 0): ?>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="regNo" value="<?php echo htmlspecialchars($player['regNo']); ?>">
                                    <input type="hidden" name="eventName" value="<?php echo htmlspecialchars($selectedEvent); ?>">
                                    <button type="submit" name="playEvent" class="btn btn-play">Play</button>
                                </form>
                            <?php else: ?>
                                <div>
                                    <button class="btn btn-played" disabled>Played (<?php echo $player['play_count']; ?>)</button>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="regNo" value="<?php echo htmlspecialchars($player['regNo']); ?>">
                                        <input type="hidden" name="eventName" value="<?php echo htmlspecialchars($selectedEvent); ?>">
                                        <button type="submit" name="replayEvent" class="btn btn-replay"
                                            <?php echo ($player['credits'] < getEventCredits($selectedEvent)) ? 'disabled' : ''; ?>>
                                            Replay (<?php echo getEventCredits($selectedEvent); ?> credits)
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                    <td>
                        <?php if ($selectedEvent): ?>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="regNo" value="<?php echo htmlspecialchars($player['regNo']); ?>">
                                <input type="hidden" name="eventName" value="<?php echo htmlspecialchars($selectedEvent); ?>">
                                <button type="submit" name="deletePlayer" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this entry?');">Delete</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($success): ?>
        <div class="popup success show"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="popup error show"><?php echo $error; ?></div>
    <?php endif; ?>

    <script>
        function filterPlayers() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('playersTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td')[2];
                if (td) {
                    const txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }

        function filterByEvent(eventName) {
            if (eventName) {
                window.location.href = `?event=${encodeURIComponent(eventName)}`;
            } else {
                window.location.href = '?';
            }
        }

        function exportPlayers() {
            window.location.href = `?export=1&event=${encodeURIComponent('<?php echo $selectedEvent; ?>')}`;
        }

        window.onload = function() {
            const popup = document.querySelector('.popup.show');
            if (popup) {
                setTimeout(function() {
                    popup.classList.remove('show');
                }, 3000);
            }
        };
    </script>
</body>

</html>
