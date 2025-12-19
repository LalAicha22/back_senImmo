<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

try {
  $stmt = $pdo->query('SELECT id, titre, description, lieu, image_url, user_id, created_at FROM incidents ORDER BY id DESC');
  $rows = $stmt->fetchAll();
  echo json_encode(['ok'=>true, 'data'=>$rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'server_error','message'=>$e->getMessage()]);
}
