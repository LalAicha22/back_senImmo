<?php
// ---- REMPLIS D'ABORD CES VALEURS SELON TON ENV ----
$DB_HOST = '127.0.0.1';   // XAMPP/MAMP locaux -> 127.0.0.1
$DB_PORT = '3306';        // MAMP: 8889 | XAMPP: 3306 | Docker: 3306
$DB_NAME = 'senimmo';
$DB_USER = 'root';        // MAMP: 'root'
$DB_PASS = '';            // MAMP: 'root' (souvent), XAMPP: '' par défaut
$UNIX_SOCKET = '';        // ex: '/var/run/mysqld/mysqld.sock' si besoin
// ----------------------------------------------------

// DSN avec port ou socket (si défini)
$dsn = $UNIX_SOCKET
  ? "mysql:unix_socket=$UNIX_SOCKET;dbname=$DB_NAME;charset=utf8mb4"
  : "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4";

$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

try {
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (Throwable $e) {
  http_response_code(500);
  header('Content-Type: application/json');
  echo json_encode(['ok' => false, 'error' => 'db_connect_failed', 'message' => $e->getMessage(), 'dsn'=>$dsn]);
  exit;
}

// CORS dev
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }
