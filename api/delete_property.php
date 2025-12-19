<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

try {
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'bad_request','message'=>'id manquant']);
    exit;
  }

  $stmt = $pdo->prepare('DELETE FROM incidents WHERE id = :id');
  $stmt->execute([':id' => $id]);

  echo json_encode(['ok'=>true, 'deleted_id'=>$id]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'server_error','message'=>$e->getMessage()]);
}
