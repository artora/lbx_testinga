<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Verify current password (assuming it's stored in config or database)
    if ($currentPassword !== 'Admin') { // Replace with actual password verification
        header('Location: admin.php?error=Current password is incorrect');
        exit();
    }

    if ($newPassword !== $confirmPassword) {
        header('Location: admin.php?error=New passwords do not match');
        exit();
    }

    // Update password in database or config
    // For this example, we'll just show success
    header('Location: admin.php?success=Password changed successfully');
    exit();
}
?>
