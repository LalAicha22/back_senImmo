<?php
header("Content-Type: application/json");
require_once("../config/database.php");

$data = json_decode(file_get_contents("php://input"), true);

if (
    empty($data["proprietaire_id"]) ||
    empty($data["titre"]) ||
    empty($data["categorie"]) ||
    empty($data["type"]) ||
    empty($data["prix"])
) {
    echo json_encode(["message" => "Champs obligatoires manquants"]);
    exit;
}

$db = (new Database())->getConnection();

$sql = "INSERT INTO proprietes 
(proprietaire_id, titre, categorie, type, statut, prix, description, imageurl)
VALUES (:proprietaire_id, :titre, :categorie, :type, :statut, :prix, :description, :imageurl)";

$stmt = $db->prepare($sql);

$stmt->execute([
    ":proprietaire_id" => $data["proprietaire_id"],
    ":titre" => $data["titre"],
    ":categorie" => $data["categorie"],
    ":type" => $data["type"],
    ":statut" => $data["statut"] ?? "disponible",
    ":prix" => $data["prix"],
    ":description" => $data["description"] ?? null,
    ":imageurl" => $data["imageurl"] ?? null
]);

echo json_encode([
    "message" => "Propriété ajoutée avec succès",
    "id" => $db->lastInsertId()
]);
