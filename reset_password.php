<?php
// reset_password.php: valida token y cambia la contraseña
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode(['error' => 'Excepción: ' . $e->getMessage()]);
    exit;
});
header('Content-Type: application/json');
require_once 'db.php';

$token = trim($_POST['token'] ?? '');
$password = $_POST['password'] ?? '';
if (!$token || !$password) {
    echo json_encode(['error' => 'Datos requeridos']);
    exit;
}
// Buscar token válido
$stmt = $pdo->prepare('SELECT pr.id, pr.user_id, u.email FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used = 0');
$stmt->execute([$token]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo json_encode(['error' => 'Token inválido o caducado']);
    exit;
}
// Cambiar la contraseña del usuario
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
$stmt->execute([$hash, $row['user_id']]);
// Marcar token como usado
$stmt = $pdo->prepare('UPDATE password_resets SET used = 1 WHERE id = ?');
$stmt->execute([$row['id']]);
echo json_encode(['success' => true]);
