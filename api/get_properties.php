<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

try {
  // ===============================
  // 1. RÉCUPÉRATION DES PARAMÈTRES
  // ===============================
  $search = trim($_GET['search'] ?? '');
  $categorie = trim($_GET['categorie'] ?? ''); // vente ou location
  $type = trim($_GET['type'] ?? ''); // maison, studio ou appartement
  $statut = trim($_GET['statut'] ?? '');

  // ===============================
  // 2. CONSTRUCTION DE LA REQUÊTE SQL DYNAMIQUE
  // ===============================
  
  // Base de la requête
  $sql = "
    SELECT 
      p.id,
      p.titre,
      p.description,
      p.categorie,
      p.type,
      p.prix,
      p.superficie,
      p.adresse,
      p.statut,
      p.imageurl,
      p.proprietaire_id,
      p.created_at,
      p.updated_at
    FROM proprietes p
    WHERE 1=1
  ";

  $params = [];

  // ✅ Filtre par recherche (tous les champs)
  if ($search !== '') {
    $sql .= " AND (
      p.titre LIKE :search
      OR p.description LIKE :search
      OR p.type LIKE :search
      OR p.categorie LIKE :search
      OR p.adresse LIKE :search
      OR p.statut LIKE :search
      OR CAST(p.prix AS CHAR) LIKE :search
      OR CAST(p.superficie AS CHAR) LIKE :search
    )";
    $params[':search'] = '%' . $search . '%';
  }

  // ✅ Filtre par CATEGORIE (vente/location)
  if ($categorie !== '') {
    $sql .= " AND p.categorie = :categorie";
    $params[':categorie'] = $categorie;
  }

  // ✅ Filtre par TYPE (maison/appartement/studio)
  if ($type !== '') {
    $sql .= " AND p.type = :type";
    $params[':type'] = $type;
  }

  // ✅ Filtre par STATUT (disponible/indisponible)
  if ($statut !== '') {
    $sql .= " AND p.statut = :statut";
    $params[':statut'] = $statut;
  }

  // Tri par ID décroissant (les plus récents en premier)
  $sql .= " ORDER BY p.id DESC";

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
  error_log("Categorie: " . ($categorie ?: 'none'));
  error_log("Type: " . ($type ?: 'none'));
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
      'categorie' => $categorie,
      'type' => $type,
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