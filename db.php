<?php
// db.php: conexión centralizada a la base de datos MySQL para la app de pizarras

$DB_HOST = 'localhost';
$DB_NAME = 'wodboard';
$DB_USER = 'fransanchezrey'; // Cambia si tienes otro usuario
$DB_PASS = 'Freestyle24@';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}
?>
