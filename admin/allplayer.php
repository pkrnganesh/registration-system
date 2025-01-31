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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addCredits'])) {
        $regNo = $_POST['regNo'];
        $creditsToAdd = $_POST['creditsToAdd'];
        addCredits($conn, $regNo, $creditsToAdd);
        $success = "Credits added successfully!";
        $players = getAllPlayers($conn);
    } elseif (isset($_POST['registerEvent'])) {
        $regNo = $_POST['regNo'];
        $eventName = $_POST['eventName'];
        $eventCredits = getEventCredits($eventName);

        $playerCredits = getPlayerCredits($conn, $regNo);
        if ($playerCredits >= $eventCredits) {
            registerForEvent($conn, $eventName, $regNo, $eventCredits);
            deductCreditsForEvent($conn, $eventName, $regNo);
            $success = "Registered for event successfully!";
            $players = getAllPlayers($conn);
        } else {
            $error = "Not enough credits to register for this event!";
        }
    } elseif (isset($_POST['playEvent'])) {
        $regNo = $_POST['regNo'];
        $eventName = $_POST['eventName'];
        playEvent($conn, $eventName, $regNo);
        $success = "Event played successfully!";
        $players = getAllPlayers($conn);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['replayEvent'])) {
        $regNo = $_POST['regNo'];
        $eventName = $_POST['eventName'];
        $eventCredits = getEventCredits($eventName);
        $playerCredits = getPlayerCredits($conn, $regNo);
        if ($playerCredits >= $eventCredits) {
            replayEvent($conn, $eventName, $regNo, $eventCredits);
            $success = "Event set for replay successfully!";
            // Refresh player data
            $players = getAllPlayers($conn);
        } else {
            $error = "Not enough credits to replay this event!";
        }
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
    $stmt = $conn->prepare("INSERT INTO events (eventName, playerRegno, credits, score, played) VALUES (?, ?, ?, 0, 0)");
    $stmt->bind_param("ssi", $eventName, $regNo, $eventCredits);
    $stmt->execute();
}

function playEvent($conn, $eventName, $regNo)
{
    $stmt = $conn->prepare("UPDATE events SET played = 1 WHERE eventName = ? AND playerRegno = ?");
    $stmt->bind_param("ss", $eventName, $regNo);
    $stmt->execute();
}

function getEventCredits($eventName)
{
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
    $stmt = $conn->prepare("SELECT eventName, played, play_count FROM events WHERE playerRegno = ?");
    $stmt->bind_param("s", $regNo);
    $stmt->execute();
    $result = $stmt->get_result();
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[$row['eventName']] = [
            'played' => $row['played'],
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
    </style>

    <style>
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
    </style>
    <style>
        .btn-replay {
            background-color: #ff9800;
        }

        .btn-replay:disabled {
            background-color: #cccccc;
        }

        .event-column {
            min-width: 200px;
        }
    </style>
</head>

<body>
    <h1>Admin Dashboard</h1>

    <div class="search-bar">
        <input type="text" id="searchInput" placeholder="Search by Registration Number" onkeyup="filterPlayers()">
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
                <?php foreach (['Squid Hunt', 'Code Fighters', 'Red Light Green Light', 'Gongi', 'Dalgona Cookie', 'Dadkji', 'Temple Run', 'Code Master', 'Spell Casters', 'KBC', 'Ideathon', 'Online Housie', 'Clash Battle'] as $eventName): ?>
                    <th class="event-column"><?php echo htmlspecialchars($eventName); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($players as $player):
                $eventStatus = getPlayerEventStatus($conn, $player['regNo']);
            ?>
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
                    <?php foreach (['Squid', 'Code Fighters', 'Red Light Green Light', 'Gongi', 'Dalgona Cookie', 'Dadkji', 'Temple Run', 'Code Master', 'Spell Casters', 'KBC', 'Ideathon', 'Online Housie', 'Clash Battle'] as $eventName): ?>
                        <td class="event-column">
                            <?php if (!isset($eventStatus[$eventName])): ?>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="regNo" value="<?php echo htmlspecialchars($player['regNo']); ?>">
                                    <input type="hidden" name="eventName" value="<?php echo htmlspecialchars($eventName); ?>">
                                    <button type="submit" name="registerEvent" class="btn btn-register">
                                        Register (<?php echo getEventCredits($eventName); ?> credits)
                                    </button>
                                </form>
                            <?php else: ?>
                                <?php if ($eventStatus[$eventName]['played'] == 0): ?>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="regNo" value="<?php echo htmlspecialchars($player['regNo']); ?>">
                                        <input type="hidden" name="eventName" value="<?php echo htmlspecialchars($eventName); ?>">
                                        <button type="submit" name="playEvent" class="btn btn-play">Play</button>
                                    </form>
                                <?php else: ?>
                                    <div>
                                        <button class="btn btn-played" disabled>Played (<?php echo $eventStatus[$eventName]['play_count']; ?>)</button>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="regNo" value="<?php echo htmlspecialchars($player['regNo']); ?>">
                                            <input type="hidden" name="eventName" value="<?php echo htmlspecialchars($eventName); ?>">
                                            <button type="submit" name="replayEvent" class="btn btn-replay"
                                                <?php echo ($player['credits'] < getEventCredits($eventName)) ? 'disabled' : ''; ?>>
                                                Replay (<?php echo getEventCredits($eventName); ?> credits)
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
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
    </script>
    <script>
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