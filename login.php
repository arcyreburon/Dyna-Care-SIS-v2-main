<?php
// MUST BE THE VERY FIRST LINE - NO WHITESPACE BEFORE THIS!
session_start();

// Store error message if it exists
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$showWelcome = !isset($_SESSION['error']); // Only show welcome if no error
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DynaCareSIS - log in</title>
    <link href="https://fonts.googleapis.com/css2?family=Lobster&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Poppins';
        }
        body {
            font-family: 'Poppins';
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }

        .background-video {
            position: absolute;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
            top: 0;
            left: 0;
        }

        /* Welcome Animation */
        .welcome-screen {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background-color: rgba(255, 179, 208, 0.9);
            z-index: 10;
            animation: fadeOut 1s forwards 2.5s;
        }
        
        .welcome-title {
            font-size: 4rem;
            font-weight: 900;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            margin-bottom: 1rem;
            opacity: 0;
            animation: fadeIn 1s forwards 0.5s;
        }
        
        .welcome-subtitle {
            font-size: 1.5rem;
            color: white;
            opacity: 0;
            animation: fadeIn 1s forwards 1s;
        }

        /* Main Container */
        .container {
            display: flex;
            width: 100%;
            max-width: 900px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            overflow: hidden;
            opacity: 0;
            transform: translateY(30px);
            animation: slideUp 0.8s forwards 2.8s;
        }
        
        .left {
            background-color: white;
            padding: 60px;
            text-align: center;
            flex: 1;
        }
        
        .left img {
            width: 120px;
            margin-bottom: 30px;
        }
        
        .left h1, .left h2 {
            font-family: 'Poppins';
        }
        
        .left h1 {
            font-size: 80px;
            margin: 30px 0 15px;
            color:rgb(0, 0, 0);
        }
        
        .left h2 {
            font-size: 25px;
            color:rgb(0, 0, 0);
            margin: 0 0 30px;
        }
        
        .right {
            background-color: rgba(255, 179, 208, 0.9);
            padding: 60px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .right h2 {
            color:rgb(0, 0, 0);
            margin-bottom: 30px;
            font-size: 28px;
        }
        
        .right input[type="text"], .right input[type="password"] {
            width: 100%;
            padding: 15px;
            margin: 15px 0;
            border: none;
            border-radius: 6px;
            font-size: 18px;
        }
        
        .right button {
            padding: 15px;
            background-color: white;
            border: none;
            border-radius: 6px;
            color:rgb(0, 0, 0);
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .right button:hover {
            background-color:rgb(255, 158, 203);
            transform: translateY(-2px);
        }
        
        .show-password {
            margin-top: 10px;
            color:rgb(0, 0, 0);
            display: block;
            font-size: 16px;
        }
        
        .alert {
            background-color:rgb(255, 99, 88);
            color: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 18px;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; visibility: hidden; }
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <video autoplay muted loop id="background-video" class="background-video">
        <source src="assets/video/DYNACARE.mp4" type="video/mp4">
    </video>

    <!-- Welcome Animation -->
    <div class="welcome-screen">
        <h1 class="welcome-title">Welcome to DynaCareSIS</h1>
        <p class="welcome-subtitle">Healthcare Solutions</p>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="left">
            <h1 style="margin-top: 60px; font-weight: 900;">DCSIS</h1>
            <hr>
            <h2 style="font-weight: 800;">HEALTH SOLUTION</h2>
        </div>
        <div class="right">
            <h2 style="font-weight: 1000    ;">LOGIN</h2>
            <div class="pt-4 pb-2">
                <?php if (!empty($error)): ?>
                    <div class="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
            </div>  
            <form class="g-3 needs-validation row" novalidate method="POST" action="php/login.php">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" id="password" name="password" placeholder="Password" required>
                <span class="show-password"><input type="checkbox" onclick="togglePassword()"> Show Password</span>
                <button type="submit" style="margin-top: 20px; font-weight: 600;">LOGIN</button>
            </form>
        </div>
    </div>

    <script>
        function togglePassword() {
            var passwordField = document.getElementById("password");
            if (passwordField.type === "password") {
                passwordField.type = "text";
            } else {
                passwordField.type = "password";
            }
        }
    </script>
</body>
</html>