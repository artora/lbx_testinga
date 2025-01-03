<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin'])) {
    die('Unauthorized access');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryName = trim($_POST['category_name']);
    $displayOrder = (int)$_POST['display_order'];

    if (!empty($categoryName)) {
        try {
            // Check if category already exists
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
            $stmt->execute([$categoryName]);
            
            if ($stmt->rowCount() === 0) {
                // Insert new category
                $stmt = $pdo->prepare("INSERT INTO categories (name, display_order) VALUES (?, ?)");
                $stmt->execute([$categoryName, $displayOrder]);
                
                header('Location: admin.php?message=Category added successfully');
            } else {
                header('Location: admin.php?error=Category already exists');
            }
        } catch (PDOException $e) {
            header('Location: admin.php?error=' . urlencode($e->getMessage()));
        }
    } else {
        header('Location: admin.php?error=Category name cannot be empty');
    }
} else {
    header('Location: admin.php');
}
exit();
?>
