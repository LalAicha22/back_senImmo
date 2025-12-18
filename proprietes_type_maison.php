<?php
header('Content-Type: application/json; charset=utf-8');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

// --------------------
// DB config (à adapter)
// --------------------
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
  echo json_encode(['ok' => false, 'message' => 'Erreur DB: ' . $e->getMessage()]);
  exit;
}

// --------------------
// Paramètres optionnels
// --------------------
$statut = isset($_GET['statut']) ? trim($_GET['statut']) : '';        // ex: disponible
$categorie = isset($_GET['categorie']) ? trim($_GET['categorie']) : ''; // ex: vente/location
$minPrix = isset($_GET['minPrix']) ? floatval($_GET['minPrix']) : null;
$maxPrix = isset($_GET['maxPrix']) ? floatval($_GET['maxPrix']) : null;

// --------------------
// Requête : type = maison
// --------------------
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
WHERE type = :type
";

$params = [':type' => 'maison'];

// filtres optionnels
if ($statut !== '') {
  $sql .= " AND statut = :statut";
  $params[':statut'] = $statut;
}

if ($categorie !== '') {
  $sql .= " AND categorie = :categorie";
  $params[':categorie'] = $categorie;
}

if ($minPrix !== null) {
  $sql .= " AND prix >= :minPrix";
  $params[':minPrix'] = $minPrix;
}

if ($maxPrix !== null && $maxPrix > 0) {
  $sql .= " AND prix <= :maxPrix";
  $params[':maxPrix'] = $maxPrix;
}

$sql .= " ORDER BY created_at DESC";

try {
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll();

  echo json_encode([
    'ok' => true,
    'type' => 'maison',
    'count' => count($rows),
    'data' => $rows
  ]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => 'Erreur requête: ' . $e->getMessage()]);
}
