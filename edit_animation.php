<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    $name = $data['name'] ?? '';
    $tags = $data['tags'] ?? '';
    $categories = $data['categories'] ?? [];
    $filePath = $data['file_path'] ?? '';

    try {
        $pdo->beginTransaction();

        if ($id) {
            // Update existing database record
            $stmt = $pdo->prepare("UPDATE animations SET name = ?, tags = ? WHERE id = ?");
            $stmt->execute([$name, $tags, $id]);

            // Update categories
            $stmt = $pdo->prepare("DELETE FROM animation_categories WHERE animation_id = ?");
            $stmt->execute([$id]);

            foreach ($categories as $categoryId) {
                $stmt = $pdo->prepare("INSERT INTO animation_categories (animation_id, category_id) VALUES (?, ?)");
                $stmt->execute([$id, $categoryId]);
            }
        } else if ($filePath) {
            // Insert new record for directory animation
            $stmt = $pdo->prepare("INSERT INTO animations (name, file_path, tags) VALUES (?, ?, ?)");
            $stmt->execute([$name, $filePath, $tags]);
            $animationId = $pdo->lastInsertId();

            foreach ($categories as $categoryId) {
                $stmt = $pdo->prepare("INSERT INTO animation_categories (animation_id, category_id) VALUES (?, ?)");
                $stmt->execute([$animationId, $categoryId]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
