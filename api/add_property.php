<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

try {
    // ===============================
    // 1. RÉCUPÉRATION DES DONNÉES
    // ===============================
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $categorie = trim($_POST['categorie'] ?? '');
    $prix = trim($_POST['prix'] ?? '');
    $superficie = trim($_POST['superficie'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $nombreChambres = trim($_POST['nombre_chambres'] ?? '');

    // Valeurs serveur
    $statut = 'disponible';
    $proprietaireId = 1; // TODO: récupérer l'utilisateur connecté

    // ===============================
    // 2. VALIDATIONS
    // ===============================
    if ($titre === '' || $description === '' || $type === '' ||
        $categorie === '' || $prix === '' || $superficie === '' || $adresse === '') {
        http_response_code(400);
        echo json_encode(['ok'=>false,'message'=>'Champs requis manquants']);
        exit;
    }

    if (!in_array($type, ['vente','location'])) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'message'=>'Type invalide']);
        exit;
    }

    if (!in_array($categorie, ['maison','studio','appartement'])) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'message'=>'Catégorie invalide']);
        exit;
    }

    if (!is_numeric($prix) || !is_numeric($superficie)) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'message'=>'Prix ou superficie invalide']);
        exit;
    }

    if ($nombreChambres !== '' && !is_numeric($nombreChambres)) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'message'=>'Nombre de chambres invalide']);
        exit;
    }

    if (empty($_FILES['image']['name'])) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'message'=>'Image requise']);
        exit;
    }

    // ===============================
    // 3. CONTRÔLE DES DOUBLONS
    // ===============================
    $stmtCheck = $pdo->prepare("
        SELECT COUNT(*) FROM property 
        WHERE LOWER(titre) = LOWER(:titre) 
          AND LOWER(adresse) = LOWER(:adresse)
          AND type = :type
    ");
    $stmtCheck->execute([
        ':titre' => $titre,
        ':adresse' => $adresse,
        ':type' => $type
    ]);
    $count = $stmtCheck->fetchColumn();
    if ($count > 0) {
        http_response_code(409); // conflit
        echo json_encode(['ok'=>false,'message'=>'Ce bien existe déjà !']);
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
    if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'message'=>'Format image invalide']);
        exit;
    }

    $filename = 'property_' . time() . '_' . random_int(1000,9999) . '.' . $ext;
    $target = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        throw new Exception('Erreur upload image');
    }

    // ===============================
    // 5. INSERTION EN BASE
    // ===============================
    $stmt = $pdo->prepare("
        INSERT INTO property
        (titre, description, type, categorie, prix, superficie, adresse,
         nombre_chambres, statut, image, proprietaire_id)
        VALUES
        (:titre, :description, :type, :categorie, :prix, :superficie, :adresse,
         :chambres, :statut, :image, :prop)
    ");

    $stmt->execute([
        ':titre' => $titre,
        ':description' => $description,
        ':type' => $type,
        ':categorie' => $categorie,
        ':prix' => $prix,
        ':superficie' => $superficie,
        ':adresse' => $adresse,
        ':chambres' => $nombreChambres !== '' ? $nombreChambres : null,
        ':statut' => $statut,
        ':image' => $filename, // ✅ Stocke seulement le nom du fichier
        ':prop' => $proprietaireId
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
    if (isset($target) && file_exists($target)) {
        unlink($target);
    }

    http_response_code(500);
    echo json_encode([
        'ok'=>false,
        'message'=>$e->getMessage()
    ]);
}
