<?php

$db_host = 'localhost';
$db_name = 'u408276378_lottie_gallery';  // Replace with your actual database name
$db_user = 'u408276378_lottie_gallery';            // Replace with your actual username
$db_pass = 'Admin@1l';            // Replace with your actual password



try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
