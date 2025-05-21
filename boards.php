<?php
// boards.php: API para listar, crear y eliminar pizarras del usuario autenticado
header('Content-Type: application/json');
require_once 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}
$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Listar pizarras del usuario
if ($action === 'list') {
    $stmt = $pdo->prepare('SELECT b.id, b.name, bm.role FROM boards b JOIN board_members bm ON b.id = bm.board_id WHERE bm.user_id = ?');
    $stmt->execute([$user_id]);
    echo json_encode(['boards' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}
// Crear nueva pizarra
if ($action === 'create') {
    $name = trim($_POST['name'] ?? '');
    if (!$name) {
        http_response_code(400);
        echo json_encode(['error' => 'Nombre requerido']);
        exit;
    }
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('INSERT INTO boards (name, created_by) VALUES (?, ?)');
    $stmt->execute([$name, $user_id]);
    $board_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare('INSERT INTO board_members (user_id, board_id, role) VALUES (?, ?, ?)');
    $stmt->execute([$user_id, $board_id, 'admin']);
    $pdo->commit();
    echo json_encode(['success' => true, 'board_id' => $board_id, 'name' => $name, 'role' => 'admin']);
    exit;
}
// Eliminar pizarra (solo admin)
if ($action === 'delete') {
    $board_id = intval($_POST['board_id'] ?? 0);
    // Verifica que el usuario sea admin
    $stmt = $pdo->prepare('SELECT role FROM board_members WHERE board_id = ? AND user_id = ?');
    $stmt->execute([$board_id, $user_id]);
    $role = $stmt->fetchColumn();
    if ($role !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Solo el admin puede eliminar la pizarra']);
        exit;
    }
    // Borra la pizarra y sus relaciones
    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM results WHERE wod_id IN (SELECT id FROM wods WHERE board_id = ?)')->execute([$board_id]);
    $pdo->prepare('DELETE FROM wods WHERE board_id = ?')->execute([$board_id]);
    $pdo->prepare('DELETE FROM board_members WHERE board_id = ?')->execute([$board_id]);
    $pdo->prepare('DELETE FROM boards WHERE id = ?')->execute([$board_id]);
    $pdo->commit();
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Acción no válida']);
