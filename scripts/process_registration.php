<?php
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $college = $_POST['college'];
    $year = $_POST['year'];

    // Check if the user already exists
    $checkUser = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $checkUser->bind_param("s", $email);
    $checkUser->execute();
    $result = $checkUser->get_result();

    if ($result->num_rows > 0) {
        echo "User already registered!";
    } else {
        // Insert the user into the database
        $stmt = $conn->prepare("INSERT INTO users (name, email, phone, college, year_of_study) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $name, $email, $phone, $college, $year);

        if ($stmt->execute()) {
            echo "Registration successful!";
            header("Location: ../public/payment.php?user_id=" . $stmt->insert_id);
        } else {
            echo "Error: " . $stmt->error;
        }
    }
}
?>
