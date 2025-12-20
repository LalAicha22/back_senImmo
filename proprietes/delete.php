<?php
header("Content-Type: application/json");
require_once("../config/database.php");

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data["id"])) {
    echo json_encode(["message" => "ID requis"]);
    exit;
}

$db = (new Database())->getConnection();

$sql = "DELETE FROM proprietes WHERE id = :id";
$stmt = $db->prepare($sql);
$stmt->execute([":id" => $data["id"]]);

echo json_encode(["message" => "Propriété supprimée avec succès"]);
