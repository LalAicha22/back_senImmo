<?php
header('Content-Type: application/json; charset=utf-8');

// ✅ CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

// --------------------------------------------------
// 1) Paramètre
// --------------------------------------------------
$firebase_uid = isset($_GET['firebase_uid']) ? trim($_GET['firebase_uid']) : '';

if ($firebase_uid === '') {
  http_response_code(400);
  echo json_encode([
    'ok' => false,
    'message' => 'Paramètre firebase_uid manquant'
  ]);
  exit;
}

// --------------------------------------------------
// 2) Connexion DB
// --------------------------------------------------
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
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
  );
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'message' => 'Erreur DB: ' . $e->getMessage()
  ]);
  exit;
}

// --------------------------------------------------
// 3) Étape 1 : récupérer l'ID SQL du propriétaire
// --------------------------------------------------
try {
  $stmt = $pdo->prepare(
    "SELECT id FROM proprietaires WHERE firebase_uid = :uid LIMIT 1"
  );
  $stmt->execute([':uid' => $firebase_uid]);
  $proprietaire = $stmt->fetch();

  if (!$proprietaire) {
    http_response_code(404);
    echo json_encode([
      'ok' => false,
      'message' => 'Propriétaire introuvable pour ce firebase_uid'
    ]);
    exit;
  }

  $proprietaire_id = (int)$proprietaire['id'];

} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'message' => 'Erreur récupération propriétaire: ' . $e->getMessage()
  ]);
  exit;
}

// --------------------------------------------------
// 4) Étape 2 : récupérer les propriétés
// --------------------------------------------------
try {
  $stmt = $pdo->prepare(
    "SELECT
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
     WHERE proprietaire_id = :pid
     ORDER BY created_at DESC"
  );

  $stmt->execute([':pid' => $proprietaire_id]);
  $rows = $stmt->fetchAll();

  echo json_encode([
    'ok' => true,
    'proprietaire_id' => $proprietaire_id,
    'count' => count($rows),
    'data' => $rows
  ]);

} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'message' => 'Erreur récupération propriétés: ' . $e->getMessage()
  ]);
}
