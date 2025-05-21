<?php
// join.php: Procesa la aceptación de una invitación usando un código
header('Content-Type: application/json');
require_once 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}
$user_id = $_SESSION['user_id'];
$code = $_POST['code'] ?? '';
if (!$code) {
    http_response_code(400);
    echo json_encode(['error' => 'Código de invitación requerido']);
    exit;
}
// Busca invitación pendiente
$stmt = $pdo->prepare('SELECT id, board_id FROM board_members WHERE invite_code = ? AND user_id IS NULL AND role = "member"');
$stmt->execute([$code]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    http_response_code(404);
    echo json_encode(['error' => 'Invitación no válida o ya utilizada']);
    exit;
}
// Comprueba si el usuario ya es miembro
$stmt = $pdo->prepare('SELECT COUNT(*) FROM board_members WHERE board_id = ? AND user_id = ?');
$stmt->execute([$row['board_id'], $user_id]);
if ($stmt->fetchColumn() > 0) {
    // Limpia la invitación pendiente
    $pdo->prepare('DELETE FROM board_members WHERE id = ?')->execute([$row['id']]);
    echo json_encode(['error' => 'Ya eres miembro de esta pizarra']);
    exit;
}
// Actualiza la invitación con el user_id
$stmt = $pdo->prepare('UPDATE board_members SET user_id = ?, joined_at = NOW(), invite_code = NULL WHERE id = ?');
$stmt->execute([$user_id, $row['id']]);
// Obtén info de la pizarra
$stmt = $pdo->prepare('SELECT name FROM boards WHERE id = ?');
$stmt->execute([$row['board_id']]);
$board_name = $stmt->fetchColumn();
// Devuelve info para frontend
echo json_encode([
    'success' => true,
    'board_id' => $row['board_id'],
    'board_name' => $board_name,
    'role' => 'member'
]);
