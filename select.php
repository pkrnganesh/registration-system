<?php
include './includes/db.php';

// SQL query to select all data from the players table
$sql = "SELECT * FROM players";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Output data of each row
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row["id"]. " - Email: " . $row["email"]. " - Phone Number: " . $row["phoneNumber"]. " - Name: " . $row["name"]. " - Reg No: " . $row["regNo"]. " - Branch: " . $row["branch"]. " - Year: " . $row["year"]. " - Credits: " . $row["credits"]. " - Events Played: " . $row["eventsPlayed"]. " - Unique ID: " . $row["uniqueId"]. "<br>";
    }
} else {
    echo "No player found";
}

// Close the database connection
$conn->close();
?>
