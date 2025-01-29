<?php
session_start();
include '../includes/db.php';

if (isset($_POST['email']) && isset($_POST['regNo'])) {
    $email = $_POST['email'];
    $regNo = $_POST['regNo'];

    $query = "SELECT * FROM players WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if ($user['regNo'] == $regNo) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_data'] = $user;
            echo json_encode(['success' => true, 'message' => "Login successful!"]);
        } else {
            echo json_encode(['success' => false, 'message' => "Invalid credentials!"]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => "Wrong credentials!"]);
    }

    $stmt->close();
    $conn->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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
</head>

<body>
    <h2>Login</h2>

    <?php if (isset($_SESSION['registration_success'])): ?>
        <div id="popup" class="popup success show"><?php echo $_SESSION['registration_success']; ?></div>
        <?php unset($_SESSION['registration_success']); ?>
    <?php endif; ?>

    <div id="popup" class="popup"></div>

    <form id="loginForm">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>

        <label for="regNo">Registration Number:</label>
        <input type="text" id="regNo" name="regNo" required>

        <button type="submit">Login</button>
    </form>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(this);
            const popup = document.getElementById('popup');

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    popup.className = 'popup success show';
                    popup.textContent = data.message;
                    window.location.href = 'dashboard.php';
                } else {
                    popup.className = 'popup error show';
                    popup.textContent = data.message;
                    setTimeout(function() {
                        popup.classList.remove('show');
                    }, 3000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });

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
