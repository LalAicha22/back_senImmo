<?php
header("Content-Type: application/json");
require_once("../config/database.php");

$data = json_decode(file_get_contents("php://input"), true);

if (
    empty($data["firebase_uid"]) ||
    empty($data["titre"]) ||
    empty($data["categorie"]) ||
    empty($data["type"]) ||
    !isset($data["prix"])   // prix peut être 0, donc pas empty()
) {
    echo json_encode(["ok" => false, "message" => "Champs obligatoires manquants"]);
    exit;
}

$firebase_uid = trim($data["firebase_uid"]);

try {
    $db = (new Database())->getConnection();

    // 1) Chercher proprietaire_id à partir du firebase_uid
    $sqlOwner = "SELECT id FROM proprietaires WHERE firebase_uid = :firebase_uid LIMIT 1";
    $stmtOwner = $db->prepare($sqlOwner);
    $stmtOwner->execute([":firebase_uid" => $firebase_uid]);
    $owner = $stmtOwner->fetch(PDO::FETCH_ASSOC);

    if (!$owner) {
        echo json_encode([
            "ok" => false,
            "message" => "Aucun propriétaire trouvé pour ce firebase_uid"
        ]);
        exit;
    }

    $proprietaire_id = (int)$owner["id"];

    // 2) Insérer la propriété
    $sql = "INSERT INTO proprietes 
        (proprietaire_id, titre, categorie, type, statut, prix, adresse, surface, description, imageurl)
        VALUES
        (:proprietaire_id, :titre, :categorie, :type, :statut, :prix, :adresse, :surface, :description, :imageurl)";

    $stmt = $db->prepare($sql);

    $stmt->execute([
        ":proprietaire_id" => $proprietaire_id,
        ":titre" => $data["titre"],
        ":categorie" => $data["categorie"],
        ":type" => $data["type"],
        ":statut" => $data["statut"] ?? "disponible",
        ":prix" => $data["prix"],
        ":adresse" => $data["adresse"] ?? "",
        ":surface" => $data["surface"] ?? 0,
        ":description" => $data["description"] ?? null,
        ":imageurl" => $data["imageurl"] ?? null,
    ]);

    echo json_encode([
        "ok" => true,
        "message" => "Propriété ajoutée avec succès",
        "proprietaire_id" => $proprietaire_id,
        "id" => $db->lastInsertId()
    ]);

} catch (Exception $e) {
    echo json_encode([
        "ok" => false,
        "message" => "Erreur serveur",
        "error" => $e->getMessage()
    ]);
}
