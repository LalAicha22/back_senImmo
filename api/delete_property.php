<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

try {
    $id = $_POST['id'] ?? null;
    if (!$id) {
        echo json_encode(['ok' => false, 'message' => "ID requis"]);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM proprietes WHERE id = :id");
    $stmt->execute([':id' => $id]);

    echo json_encode(['ok' => true, 'message' => 'Supprimé']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
