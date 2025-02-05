<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player Management System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        h1 {
            color: white;
            text-align: center;
            grid-column: 1 / -1;
            font-size: 2.5rem;
            margin-bottom: 40px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .card {
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0,0,0,0.2);
        }

        .card-title {
            font-size: 1.4rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: #4a5568;
        }

        .card-description {
            font-size: 0.9rem;
            color: #666;
            line-height: 1.5;
        }

        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
                padding: 15px;
            }
            
            h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Player Management System</h1>
        
        <a href="allplayer.php" class="card">
            <div class="card-title">All Players</div>
            <div class="card-description">View complete player database and details</div>
        </a>

        <a href="eventDetails.php" class="card">
            <div class="card-title">Event Details</div>
            <div class="card-description">Access player information for specific events</div>
        </a>

        <a href="filterplayers.php" class="card">
            <div class="card-title">Search Players</div>
            <div class="card-description">Search and filter player database</div>
        </a>

        <a href="index.php" class="card">
            <div class="card-title">Scan QR Code</div>
            <div class="card-description">Scan player QR codes for quick access</div>
        </a>
    </div>
</body>
</html>