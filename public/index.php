<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tech Fest 2025</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #000;
            color: #fff;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow: hidden;
        }

        .container {
            text-align: center;
            background-color: rgba(20, 20, 20, 0.9);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 30px rgba(255, 0, 0, 0.3);
            position: relative;
            max-width: 600px;
            width: 90%;
            border: 2px solid #ff0000;
        }

        h1 {
            font-size: 3em;
            color: #ff0000;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        .subheading {
            color: #00ff00;
            font-size: 1.2em;
            margin-bottom: 30px;
        }

        .register-btn {
            display: inline-block;
            background-color: #ff0000;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            text-transform: uppercase;
            transition: all 0.3s ease;
            border: 2px solid #ff0000;
        }

        .register-btn:hover {
            background-color: transparent;
            color: #ff0000;
        }

        .game-shapes {
            position: absolute;
            top: -50px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 20px;
        }

        .shape {
            width: 50px;
            height: 50px;
            background-color: #ff0000;
            clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
            animation: pulse 1.5s infinite alternate;
        }

        @keyframes pulse {
            from {
                transform: scale(1);
                opacity: 0.7;
            }
            to {
                transform: scale(1.2);
                opacity: 1;
            }
        }

        .details {
            margin-top: 30px;
            color: #888;
            font-size: 0.9em;
        }

        @media (max-width: 600px) {
            .container {
                padding: 20px;
            }

            h1 {
                font-size: 2em;
            }

            .game-shapes {
                top: -30px;
            }

            .shape {
                width: 30px;
                height: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="game-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>
        <h1>Tech Fest 2025</h1>
        <div class="subheading">Survival of the Smartest</div>
        <p>Compete in epic tech challenges. Only the most skilled will survive!</p>
        <a href="register.php" class="register-btn">Register Now</a>
        <div class="details">
            Limited Slots | High Stakes | Ultimate Tech Challenge
        </div>
    </div>
</body>
</html>