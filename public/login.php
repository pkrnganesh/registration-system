<?php
session_start();
include '../includes/db.php';

if (isset($_POST['email']) && isset($_POST['regNo'])) {
    $email = $_POST['email'];
    $regNo = strtoupper($_POST['regNo']);

    $query = "SELECT * FROM players WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (strtoupper($user['regNo']) == $regNo) {
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #121212;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            color: white;
        }

        .popup {
            visibility: hidden;
            width: 100%;
            max-width: 400px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 8px;
            padding: 16px;
            position: fixed;
            z-index: 1000;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 16px;
            opacity: 0;
            transition: opacity 0.3s, transform 0.3s;
        }

        .popup.show {
            visibility: visible;
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        .popup.success {
            background-color: #28a745;
        }

        .popup.error {
            background-color: #cc0000;
        }

        .logo-container {
            margin-bottom: 30px;
            text-align: center;
            margin-top: 60px;
        }

        .logo {
            font-size: 36px;
            font-weight: bold;
            color: white;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }

        .logo span {
            color: #cc0000;
        }

        .welcome-text {
            text-align: center;
            font-size: 18px;
            margin-bottom: 30px;
            color: white;
        }

        .container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        input {
            width: 100%;
            padding: 15px;
            background-color: #2a2a2a;
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
        }

        input::placeholder {
            color: #666;
        }

        button {
            width: 100%;
            padding: 15px;
            background-color: #cc0000;
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #aa0000;
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #cc0000;
            text-decoration: underline;
            cursor: pointer;
        }

        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }

            input,
            button {
                font-size: 14px;
            }

            .popup {
                width: calc(100% - 40px);
            }
        }
    </style>
</head>

<body>
    <div id="popup" class="popup"></div>

    <div class="container">
        <div class="logo-container">
            <div class="logo">SIGMA<span>2K25</span></div>
        </div>

        <div class="welcome-text">WELCOME TO THE SIGMA FEST</div>

        <?php if (isset($_SESSION['registration_success'])): ?>
            <div id="success-popup" class="popup success show"><?php echo $_SESSION['registration_success']; ?></div>
            <?php unset($_SESSION['registration_success']); ?>
        <?php endif; ?>

        <form id="loginForm">
            <div class="form-group">
                <input type="email" id="email" name="email" placeholder="Email" required>
            </div>

            <div class="form-group">
                <input type="text" id="regNo" name="regNo" placeholder="Registration number" required>
            </div>

            <button type="submit">Login</button>
        </form>

       <div class="register-link" onclick="redirectToRegister()"> If not registered?Go Register </div>
    </div>

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
                        window.location.href = 'newdashboard.php';
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

        function redirectToRegister() {
            window.location.href = 'register.php';
        }
    </script>
</body>

</html>