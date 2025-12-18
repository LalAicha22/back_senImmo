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
// 1) Paramètre obligatoire: type
// --------------------
$type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : '';

$allowedTypes = ['maison', 'appartement', 'studio'];
if ($type === '' || !in_array($type, $allowedTypes, true)) {
  http_response_code(400);
  echo json_encode([
    'ok' => false,
    'message' => "Paramètre 'type' invalide. Valeurs possibles: maison, appartement, studio."
  ]);
  exit;
}

// --------------------
// 2) DB config (à adapter)
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
// 3) Paramètres optionnels (filtres)
// --------------------
$statut    = isset($_GET['statut']) ? trim($_GET['statut']) : '';       // disponible/indisponible
$categorie = isset($_GET['categorie']) ? trim($_GET['categorie']) : ''; // vente/location
$minPrix   = (isset($_GET['minPrix']) && $_GET['minPrix'] !== '') ? floatval($_GET['minPrix']) : null;
$maxPrix   = (isset($_GET['maxPrix']) && $_GET['maxPrix'] !== '') ? floatval($_GET['maxPrix']) : null;

// --------------------
// 4) Requête SQL dynamique (sécurisée)
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

$params = [':type' => $type];

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

if ($maxPrix !== null) {
  $sql .= " AND prix <= :maxPrix";
  $params[':maxPrix'] = $maxPrix;
}

$sql .= " ORDER BY created_at DESC";

// --------------------
// 5) Exécution + réponse
// --------------------
try {
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll();

  echo json_encode([
    'ok' => true,
    'type' => $type,
    'count' => count($rows),
    'data' => $rows
  ]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => 'Erreur requête: ' . $e->getMessage()]);
}
