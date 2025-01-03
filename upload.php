<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

function generateUniqueFileName($originalName, $uploadDir) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $basename = preg_replace("/[^a-zA-Z0-9]/", "-", pathinfo($originalName, PATHINFO_FILENAME));
    $filename = $basename . "." . $extension;
    $counter = 1;
    
    while (file_exists($uploadDir . $filename)) {
        $filename = $basename . "-" . $counter . "." . $extension;
        $counter++;
    }
    
    return $filename;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $uploadDir = 'uploads/';
        $name = $_POST['name'] ?? '';
        $tags = $_POST['tags'] ?? '';
        $categories = explode(',', $_POST['categories'] ?? '');
        
        // Handle file upload
        if (isset($_FILES['lottieFile']) && $_FILES['lottieFile']['error'] === 0) {
            $fileName = generateUniqueFileName($_FILES['lottieFile']['name'], $uploadDir);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['lottieFile']['tmp_name'], $targetPath)) {
                // Insert animation
                $stmt = $pdo->prepare("INSERT INTO animations (name, file_path, tags) VALUES (?, ?, ?)");
                $stmt->execute([$name, $targetPath, $tags]);
                $animationId = $pdo->lastInsertId();

                // Insert categories
                foreach ($categories as $categoryId) {
                    if (!empty($categoryId)) {
                        $stmt = $pdo->prepare("INSERT INTO animation_categories (animation_id, category_id) VALUES (?, ?)");
                        $stmt->execute([$animationId, $categoryId]);
                    }
                }
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Failed to move uploaded file');
            }
        } 
        // Handle URL import
        elseif (!empty($_POST['url'])) {
            $stmt = $pdo->prepare("INSERT INTO animations (name, url, tags) VALUES (?, ?, ?)");
            $stmt->execute([$name, $_POST['url'], $tags]);
            $animationId = $pdo->lastInsertId();

            // Insert categories
            foreach ($categories as $categoryId) {
                if (!empty($categoryId)) {
                    $stmt = $pdo->prepare("INSERT INTO animation_categories (animation_id, category_id) VALUES (?, ?)");
                    $stmt->execute([$animationId, $categoryId]);
                }
            }
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('No file or URL provided');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
