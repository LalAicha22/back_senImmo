<?php
header("Content-Type: application/json");
require_once("../config/database.php");

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data["id"])) {
    echo json_encode(["message" => "ID de la propriété requis"]);
    exit;
}

$db = (new Database())->getConnection();

$sql = "UPDATE proprietes SET
    titre = :titre,
    categorie = :categorie,
    type = :type,
    statut = :statut,
    prix = :prix,
    description = :description,
    imageurl = :imageurl
WHERE id = :id";

$stmt = $db->prepare($sql);

$stmt->execute([
    ":id" => $data["id"],
    ":titre" => $data["titre"],
    ":categorie" => $data["categorie"],
    ":type" => $data["type"],
    ":statut" => $data["statut"],
    ":prix" => $data["prix"],
    ":description" => $data["description"],
    ":imageurl" => $data["imageurl"]
]);

echo json_encode(["message" => "Propriété modifiée avec succès"]);
