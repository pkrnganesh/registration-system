<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #000000;
            color: #ffffff;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }

        h2 {
            text-align: center;
            margin: 20px 0;
            color: #ffffff;
            font-size: 24px;
        }

        form {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        input, select {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            background-color:rgb(46, 42, 42);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
        }

        input::placeholder, select::placeholder {
            color: #666;
        }

        select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='white' viewBox='0 0 16 16'%3E%3Cpath d='M8 11l-7-7h14l-7 7z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
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
        }

        button:hover {
            background-color: #aa0000;
        }

        .popup {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            padding: 15px 25px;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            display: none;
            z-index: 1000;
        }

        .popup.error {
            background-color: #cc0000;
        }

        .popup.show {
            display: block;
        }

        .login-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #cc0000;
            text-decoration: none;
            font-size: 16px;
        }

        .login-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            form {
                padding: 10px;
            }

            input, select, button {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <h2>Register for SIGMA2K25</h2>
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
        $credits = 0;
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

    <form action="" method="POST" id="registrationForm">
        <div class="form-group">
            <input type="text" id="name" name="name" placeholder="Name" value="<?php echo htmlspecialchars($name); ?>" required>
        </div>

        <div class="form-group">
            <input type="email" id="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>

        <div class="form-group">
            <input type="tel" id="phoneNumber" name="phoneNumber" placeholder="Phone Number" value="<?php echo htmlspecialchars($phoneNumber); ?>" maxlength="10" required>
        </div>

        <div class="form-group">
            <input type="text" id="regNo" name="regNo" placeholder="Registration Number" value="<?php echo htmlspecialchars($regNo); ?>" maxlength="10" required>
        </div>

        <div class="form-group">
            <select id="branch" name="branch" required>
                <option value="">Select Branch</option>
                <option value="CSD" <?php echo ($branch === 'CSD') ? 'selected' : ''; ?>>CSD</option>
                <option value="CSE" <?php echo ($branch === 'CSE') ? 'selected' : ''; ?>>CSE</option>
                <option value="IT" <?php echo ($branch === 'IT') ? 'selected' : ''; ?>>IT</option>
                <option value="ECE" <?php echo ($branch === 'ECE') ? 'selected' : ''; ?>>ECE</option>
                <option value="EEE" <?php echo ($branch === 'EEE') ? 'selected' : ''; ?>>EEE</option>
                <option value="MECH" <?php echo ($branch === 'MECH') ? 'selected' : ''; ?>>MECH</option>
                <option value="CIVIL" <?php echo ($branch === 'CIVIL') ? 'selected' : ''; ?>>CIVIL</option>
            </select>
        </div>

        <div class="form-group">
            <select id="year" name="year" required>
                <option value="">Select Year</option>
                <option value="1" <?php echo ($year === '1') ? 'selected' : ''; ?>>1</option>
                <option value="2" <?php echo ($year === '2') ? 'selected' : ''; ?>>2</option>
                <option value="3" <?php echo ($year === '3') ? 'selected' : ''; ?>>3</option>
                <option value="4" <?php echo ($year === '4') ? 'selected' : ''; ?>>4</option>
            </select>
        </div>

        <button type="submit">Register</button>
    </form>

    <a href="../public/login.php" class="login-link">Already registered? Go to Login</a>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registrationForm');
            const phoneInput = document.getElementById('phoneNumber');
            const regNoInput = document.getElementById('regNo');

            // Phone number validation (numbers only)
            phoneInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
            });

            // Form validation
            form.addEventListener('submit', function(e) {
                const phone = phoneInput.value;
                const regNo = regNoInput.value;
                const email = document.getElementById('email').value;

                if (phone.length !== 10) {
                    e.preventDefault();
                    showError('Phone number must be exactly 10 digits');
                    return;
                }

                if (regNo.length !== 10) {
                    e.preventDefault();
                    showError('Registration number must be exactly 10 characters');
                    return;
                }

                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    e.preventDefault();
                    showError('Please enter a valid email address');
                    return;
                }
            });

            // Error popup handling
            const popup = document.getElementById('popup');
            if (popup) {
                setTimeout(function() {
                    popup.classList.remove('show');
                }, 3000);
            }

            function showError(message) {
                const popup = document.createElement('div');
                popup.className = 'popup error show';
                popup.textContent = message;
                document.body.appendChild(popup);

                setTimeout(function() {
                    popup.remove();
                }, 3000);
            }
        });
    </script>
</body>
</html>
