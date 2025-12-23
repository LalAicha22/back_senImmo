<?php
header('Content-Type: application/json; charset=utf-8');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

$propriete_id = isset($_GET['propriete_id']) ? (int) $_GET['propriete_id'] : 0;

if ($propriete_id <= 0) {
  http_response_code(400);
  echo json_encode([
    'ok' => false,
    'message' => 'Paramètre invalide: propriete_id'
  ]);
  exit;
}

// DB config
$DB_HOST = "localhost";
$DB_NAME = "senimmo";
$DB_USER = "root";
$DB_PASS = "";

try {
  $pdo = new PDO(
    "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
    $DB_USER,
    $DB_PASS,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
  );
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => 'Erreur DB: ' . $e->getMessage()]);
  exit;
}

$sql = "
SELECT
  id,
  proprietaire_id,
  titre,
  categorie,
  type,
  statut,
  prix,
  description,
  created_at,
  updated_at
FROM proprietes
WHERE id = :pid
LIMIT 1
";

try {
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':pid' => $propriete_id]);
  $row = $stmt->fetch();

  if (!$row) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => "Propriété introuvable."]);
    exit;
  }

  echo json_encode(['ok' => true, 'data' => $row], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => 'Erreur requête: ' . $e->getMessage()]);
}
