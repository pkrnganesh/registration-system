<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    
    <link rel="stylesheet" href="../assets/public/register.css">
    
</head>
<body>
    <h2>Register for the Fest</h2>
    <?php
    session_start();
    include '../includes/db.php';

    $name = $email = $phoneNumber = $regNo = $branch = $year = $credits = '';
    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phoneNumber = $_POST['phoneNumber'];
        $regNo = $_POST['regNo'];
        $branch = $_POST['branch'];
        $year = $_POST['year'];

        $randomNumber = mt_rand(1, 999);
        $uniqueId = $regNo . "SIGMA2K25" . "No" . sprintf("%03d", $randomNumber);
        $credits=0;
        $eventsPlayed = 0;

        $checkUser = $conn->prepare("SELECT * FROM players WHERE email = ? OR regNo = ?");
        $checkUser->bind_param("ss", $email, $regNo);
        $checkUser->execute();
        $result = $checkUser->get_result();

        if ($result->num_rows > 0) {
            $error = 'User already registered!';
        } else {
            $stmt = $conn->prepare("INSERT INTO players (email, phoneNumber, name, regNo, branch, year, credits, eventsPlayed, uniqueId) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssiiis", $email, $phoneNumber, $name, $regNo, $branch, $year, $credits, $eventsPlayed, $uniqueId);

            if ($stmt->execute()) {
                $_SESSION['registration_success'] = "Registered successfully!";
                header("Location: ../public/login.php");
                exit();
            } else {
                $error = 'Error: ' . $stmt->error;
            }

            $stmt->close();
        }

        $checkUser->close();
    }

    $conn->close();
    ?>

    <?php if ($error): ?>
        <div id="popup" class="popup error show"><?php echo $error; ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>

        <label for="phoneNumber">Phone Number:</label>
        <input type="tel" id="phoneNumber" name="phoneNumber" value="<?php echo htmlspecialchars($phoneNumber); ?>" required>

        <label for="regNo">Registration Number:</label>
        <input type="text" id="regNo" name="regNo" value="<?php echo htmlspecialchars($regNo); ?>" required>

        <label for="branch">Branch:</label>
        <input type="text" id="branch" name="branch" value="<?php echo htmlspecialchars($branch); ?>" required>

        <label for="year">Year of Study:</label>
        <input type="number" id="year" name="year" value="<?php echo htmlspecialchars($year); ?>" min="1" max="4" required>


        <button type="submit">Submit</button>
    </form>
    

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
