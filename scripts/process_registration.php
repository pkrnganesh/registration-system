<?php
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phoneNumber = $_POST['phoneNumber'];
    $regNo = $_POST['regNo'];
    $credits = $_POST['credits'];
    $branch = $_POST['branch'];
    $year = $_POST['year'];

    $randomNumber = mt_rand(1, 999);
    $uniqueId = $regNo . "SIGMA2K25" . "No" . sprintf("%03d", $randomNumber);

    $eventsPlayed = 0;

    $checkUser = $conn->prepare("SELECT * FROM players WHERE email = ? OR regNo = ?");
    $checkUser->bind_param("ss", $email, $regNo);
    $checkUser->execute();
    $result = $checkUser->get_result();

    if ($result->num_rows > 0) {
        echo "User already registered!";
    } else {
        $stmt = $conn->prepare("INSERT INTO players (email, phoneNumber, name, regNo, branch, year, credits, eventsPlayed, uniqueId) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssiiis", $email, $phoneNumber, $name, $regNo, $branch, $year, $credits, $eventsPlayed, $uniqueId);

        if ($stmt->execute()) {
            header("Location: ../public/login.php?message=Registration successful! Your Unique ID is: " );
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    }

    $checkUser->close();
}

$conn->close();
?>
