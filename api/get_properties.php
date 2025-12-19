<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

try {
  // ===============================
  // 1. RÉCUPÉRATION DES PARAMÈTRES
  // ===============================
  $search = trim($_GET['search'] ?? '');
  $type = trim($_GET['type'] ?? '');
  $categorie = trim($_GET['categorie'] ?? '');
  $statut = trim($_GET['statut'] ?? '');

  // ===============================
  // 2. CONSTRUCTION DE LA REQUÊTE SQL DYNAMIQUE
  // ===============================
  
  // Base de la requête
  $sql = "
    SELECT 
      id,
      titre,
      description,
      type,
      categorie,
      prix,
      superficie,
      adresse,
      nombre_chambres,
      statut,
      image,
      proprietaire_id,
      created_at
    FROM property
    WHERE 1=1
  ";

  $params = [];

  // ✅ Filtre par recherche (tous les champs)
  if ($search !== '') {
    $sql .= " AND (
      titre LIKE :search
      OR description LIKE :search
      OR type LIKE :search
      OR categorie LIKE :search
      OR adresse LIKE :search
      OR statut LIKE :search
      OR CAST(prix AS CHAR) LIKE :search
      OR CAST(superficie AS CHAR) LIKE :search
    )";
    $params[':search'] = '%' . $search . '%';
  }

  // ✅ Filtre par TYPE (location/vente)
  if ($type !== '') {
    $sql .= " AND type = :type";
    $params[':type'] = $type;
  }

  // ✅ Filtre par CATEGORIE (maison/appartement/studio)
  if ($categorie !== '') {
    $sql .= " AND categorie = :categorie";
    $params[':categorie'] = $categorie;
  }

  // ✅ Filtre par STATUT (disponible/loue/vendu)
  if ($statut !== '') {
    $sql .= " AND statut = :statut";
    $params[':statut'] = $statut;
  }

  // Tri par ID décroissant (les plus récents en premier)
  $sql .= " ORDER BY id DESC";

  // ===============================
  // 3. EXÉCUTION DE LA REQUÊTE
  // ===============================
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // ===============================
  // 4. LOG POUR DEBUG (optionnel)
  // ===============================
  error_log("=== GET PROPERTIES ===");
  error_log("Search: " . ($search ?: 'none'));
  error_log("Type: " . ($type ?: 'none'));
  error_log("Categorie: " . ($categorie ?: 'none'));
  error_log("Statut: " . ($statut ?: 'none'));
  error_log("Results: " . count($properties));

  // ===============================
  // 5. RÉPONSE API
  // ===============================
  echo json_encode([
    'ok' => true,
    'data' => $properties,
    'count' => count($properties),
    'filters' => [
      'search' => $search,
      'type' => $type,
      'categorie' => $categorie,
      'statut' => $statut
    ]
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  error_log("ERROR in get_properties.php: " . $e->getMessage());
  echo json_encode([
    'ok' => false,
    'message' => 'Erreur serveur',
    'error' => $e->getMessage() // ⚠️ À retirer en production
  ]);
}