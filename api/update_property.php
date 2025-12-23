<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

try {
    $id = $_POST['id'] ?? null;
    if (!$id) {
        echo json_encode(['ok' => false, 'message' => "ID requis"]);
        exit;
    }

    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categorie = trim($_POST['categorie'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $prix = trim($_POST['prix'] ?? '');
    $surface = trim($_POST['surface'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $statut = trim($_POST['statut'] ?? 'disponible');

    $sql = "UPDATE proprietes SET 
                titre = :titre, 
                description = :description, 
                categorie = :categorie, 
                type = :type, 
                prix = :prix, 
                surface = :surface, 
                adresse = :adresse, 
                statut = :statut";

    $params = [
        ':id' => $id,
        ':titre' => $titre,
        ':description' => $description,
        ':categorie' => $categorie,
        ':type' => $type,
        ':prix' => $prix,
        ':surface' => $surface,
        ':adresse' => $adresse,
        ':statut' => $statut
    ];

    // Gérer l'image si présente
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = dirname(__DIR__) . '/uploads/properties';
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $filename = 'property_' . time() . '_' . random_int(1000, 9999) . '.' . $ext;
        $target = $uploadDir . '/' . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $sql .= ", imageurl = :imageurl";
            $params[':imageurl'] = $filename;
        }
    }

    $sql .= " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['ok' => true, 'message' => 'Mis à jour']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
