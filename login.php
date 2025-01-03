<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['password'] === 'Admin') {
        $_SESSION['admin'] = true;
        header('Location: admin.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <style>
        body { 
            background: #1a1a1a; 
            color: white; 
            font-family: Arial; 
        }
        .login-form {
            max-width: 300px;
            margin: 100px auto;
            padding: 20px;
            background: #2d2d2d;
            border-radius: 8px;
        }
        input, button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: none;
            border-radius: 4px;
        }
        button { 
            background: #007bff; 
            color: white; 
            cursor: pointer; 
        }
        .home-link {
            position: fixed;
            top: 20px;
            right: 20px;
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <a href="index.php" class="home-link">Back to Gallery</a>
    <form method="post" class="login-form">
        <h2>Admin Login</h2>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
</body>
</html>
