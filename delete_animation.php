<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;

if ($id) {
    try {
        $pdo->beginTransaction();

        // Get file path before deletion
        $stmt = $pdo->prepare("SELECT file_path FROM animations WHERE id = ?");
        $stmt->execute([$id]);
        $animation = $stmt->fetch();

        // Delete categories associations
        $stmt = $pdo->prepare("DELETE FROM animation_categories WHERE animation_id = ?");
        $stmt->execute([$id]);

        // Delete animation record
        $stmt = $pdo->prepare("DELETE FROM animations WHERE id = ?");
        $stmt->execute([$id]);

        // Delete file if exists and is in uploads directory
        if ($animation && $animation['file_path'] && 
            strpos($animation['file_path'], 'uploads/') === 0 && 
            file_exists($animation['file_path'])) {
            unlink($animation['file_path']);
        }

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid animation ID']);
}
?>
