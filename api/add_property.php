<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

try {
    // ===============================
    // 1. RÉCUPÉRATION DES DONNÉES
    // ===============================
    $firebase_uid = trim($_POST['firebase_uid'] ?? '');
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categorie = trim($_POST['categorie'] ?? ''); // vente ou location
    $type = trim($_POST['type'] ?? ''); // maison, studio ou appartement
    $prix = trim($_POST['prix'] ?? '');
    $surface = trim($_POST['surface'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');

    // Valeurs serveur
    $statut = 'disponible';

    // ===============================
    // 2. VALIDATIONS
    // ===============================
    if (
        $firebase_uid === '' || $titre === '' || $description === '' || $type === '' ||
        $categorie === '' || $prix === '' || $adresse === ''
    ) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Champs requis manquants']);
        exit;
    }

    // 1) Chercher proprietaire_id à partir du firebase_uid
    $sqlOwner = "SELECT id FROM proprietaires WHERE firebase_uid = :firebase_uid LIMIT 1";
    $stmtOwner = $pdo->prepare($sqlOwner);
    $stmtOwner->execute([':firebase_uid' => $firebase_uid]);
    $owner = $stmtOwner->fetch(PDO::FETCH_ASSOC);

    if (!$owner) {
        http_response_code(404);
        echo json_encode([
            "ok" => false,
            "message" => "Aucun propriétaire trouvé pour ce firebase_uid"
        ]);
        exit;
    }

    $proprietaireId = (int) $owner["id"];

    // Validation de la catégorie (vente ou location)
    if (!in_array($categorie, ['vente', 'location'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Catégorie invalide (vente ou location)']);
        exit;
    }

    // Validation du type (maison, studio ou appartement)
    if (!in_array($type, ['maison', 'studio', 'appartement'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Type invalide (maison, studio ou appartement)']);
        exit;
    }

    // Validation du prix
    if (!is_numeric($prix) || $prix <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Prix invalide']);
        exit;
    }

    // Validation de la surface (optionnelle)
    if ($surface !== '' && (!is_numeric($surface) || $surface <= 0)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Surface invalide']);
        exit;
    }

    // Validation de l'image
    if (empty($_FILES['image']['name'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Image requise']);
        exit;
    }

    // ===============================
    // 3. CONTRÔLE DES DOUBLONS
    // ===============================
    $stmtCheck = $pdo->prepare("
        SELECT COUNT(*) FROM proprietes 
        WHERE LOWER(titre) = LOWER(:titre) 
          AND LOWER(adresse) = LOWER(:adresse)
          AND type = :type
          AND proprietaire_id = :prop_id
    ");
    $stmtCheck->execute([
        ':titre' => $titre,
        ':adresse' => $adresse,
        ':type' => $type,
        ':prop_id' => $proprietaireId
    ]);
    $count = $stmtCheck->fetchColumn();
    if ($count > 0) {
        http_response_code(409); // conflit
        echo json_encode(['ok' => false, 'message' => 'Ce bien existe déjà !']);
        exit;
    }

    // ===============================
    // 4. UPLOAD IMAGE
    // ===============================
    $uploadDir = dirname(__DIR__) . '/uploads/properties';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Format image invalide (jpg, jpeg, png, webp)']);
        exit;
    }

    $filename = 'property_' . time() . '_' . random_int(1000, 9999) . '.' . $ext;
    $target = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        throw new Exception('Erreur upload image');
    }

    // ===============================
    // 5. INSERTION EN BASE
    // ===============================
    $stmt = $pdo->prepare("
        INSERT INTO proprietes
        (proprietaire_id, titre, categorie, type, statut, prix, 
         adresse, surface, description, imageurl)
        VALUES
        (:prop_id, :titre, :categorie, :type, :statut, :prix, 
         :adresse, :surface, :description, :imageurl)
    ");

    $stmt->execute([
        ':prop_id' => $proprietaireId,
        ':titre' => $titre,
        ':categorie' => $categorie,
        ':type' => $type,
        ':statut' => $statut,
        ':prix' => $prix,
        ':adresse' => $adresse,
        ':surface' => $surface !== '' ? $surface : null,
        ':description' => $description,
        ':imageurl' => $filename
    ]);

    // ===============================
    // 6. RÉPONSE API
    // ===============================
    echo json_encode([
        'ok' => true,
        'message' => 'Bien créé avec succès',
        'id' => $pdo->lastInsertId(),
        'image' => $filename
    ]);

} catch (Throwable $e) {
    // Nettoyage de l'image en cas d'erreur
    if (isset($target) && file_exists($target)) {
        unlink($target);
    }

    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
