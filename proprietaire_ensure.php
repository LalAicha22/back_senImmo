<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$input = json_decode(file_get_contents("php://input"), true);
$uid = isset($input['firebase_uid']) ? trim($input['firebase_uid']) : '';

if ($uid === '') {
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>'firebase_uid manquant']);
  exit;
}

$pdo = new PDO("mysql:host=localhost;dbname=senimmo;charset=utf8mb4","root","",[
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
]);

// 1) chercher
$stmt = $pdo->prepare("SELECT id FROM proprietaires WHERE firebase_uid = :uid LIMIT 1");
$stmt->execute([':uid'=>$uid]);
$row = $stmt->fetch();

if ($row) {
  echo json_encode(['ok'=>true,'proprietaire_id'=>(int)$row['id'], 'created'=>false]);
  exit;
}

// 2) insérer si absent
$stmt = $pdo->prepare("INSERT INTO proprietaires(firebase_uid) VALUES(:uid)");
$stmt->execute([':uid'=>$uid]);

echo json_encode([
  'ok'=>true,
  'proprietaire_id'=>(int)$pdo->lastInsertId(),
  'created'=>true
]);
