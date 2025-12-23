<?php
header("Content-Type: application/json");
require_once("../config/database.php");

if (!isset($_GET["firebase_uid"]) || empty($_GET["firebase_uid"])) {
    echo json_encode([]);
    exit;
}

$firebase_uid = trim($_GET["firebase_uid"]);
$db = (new Database())->getConnection();

$sqlOwner = "SELECT id FROM proprietaires WHERE firebase_uid = :firebase_uid LIMIT 1";
$stmtOwner = $db->prepare($sqlOwner);
$stmtOwner->execute([":firebase_uid" => $firebase_uid]);
$owner = $stmtOwner->fetch(PDO::FETCH_ASSOC);

if (!$owner) {
    echo json_encode([]);
    exit;
}

$proprietaire_id = (int)$owner["id"];

$sql = "SELECT * FROM proprietes WHERE proprietaire_id = :proprietaire_id ORDER BY id DESC";
$stmt = $db->prepare($sql);
$stmt->execute([":proprietaire_id" => $proprietaire_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
