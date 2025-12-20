<?php
header("Content-Type: application/json");
require_once("../config/database.php");

if (!isset($_GET["proprietaire_id"])) {
    echo json_encode(["message" => "proprietaire_id manquant"]);
    exit;
}

$proprietaire_id = $_GET["proprietaire_id"];

$db = (new Database())->getConnection();

$sql = "SELECT * FROM proprietes WHERE proprietaire_id = :proprietaire_id ORDER BY id DESC";
$stmt = $db->prepare($sql);
$stmt->execute([
    ":proprietaire_id" => $proprietaire_id
]);

$proprietes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($proprietes);
