<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Datos no válidos']);
    exit;
}

$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Usuario no autenticado']);
    exit;
}

$board_id = isset($data['board_id']) ? intval($data['board_id']) : null;
$date = isset($data['date']) ? $data['date'] : null;

if (!$board_id || !$date) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan parámetros requeridos']);
    exit;
}

// Si el admin/coach guarda el WOD
if (isset($data['wod_text'])) {
    $wod_text = trim($data['wod_text']);
    // Comprobar si el usuario es admin/coach en esta pizarra
    $stmt = $pdo->prepare('SELECT role FROM board_members WHERE board_id = ? AND user_id = ?');
    $stmt->execute([$board_id, $user_id]);
    $role = $stmt->fetchColumn();
    if ($role !== 'admin' && $role !== 'coach') {
        echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
        exit;
    }
    // Insertar o actualizar el WOD
    $stmt = $pdo->prepare('SELECT id FROM wods WHERE board_id = ? AND date = ?');
    $stmt->execute([$board_id, $date]);
    $wod_id = $stmt->fetchColumn();
    if ($wod_id) {
        $stmt = $pdo->prepare('UPDATE wods SET wod_text = ?, created_by = ?, created_at = NOW() WHERE id = ?');
        $stmt->execute([$wod_text, $user_id, $wod_id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO wods (board_id, date, wod_text, created_by, created_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([$board_id, $date, $wod_text, $user_id]);
        $wod_id = $pdo->lastInsertId();
    }
    echo json_encode(['status' => 'success', 'wod_id' => $wod_id]);
    exit;
}

// Si un usuario quiere eliminar un resultado propio
if (isset($data['delete_result']) && $data['delete_result'] && isset($data['result_text'])) {
    $result_text = trim($data['result_text']);
    // Buscar el WOD correspondiente
    $stmt = $pdo->prepare('SELECT id FROM wods WHERE board_id = ? AND date = ?');
    $stmt->execute([$board_id, $date]);
    $wod_id = $stmt->fetchColumn();
    if (!$wod_id) {
        echo json_encode(['status' => 'error', 'message' => 'No existe un WOD para esta fecha']);
        exit;
    }
    // Eliminar SOLO el resultado concreto de ese usuario y ese texto
    $stmt = $pdo->prepare('DELETE FROM results WHERE wod_id = ? AND user_id = ? AND result_text = ? LIMIT 1');
    $stmt->execute([$wod_id, $user_id, $result_text]);
    echo json_encode(['status' => 'success', 'deleted' => true]);
    exit;
}

// Si un usuario añade un resultado
if (isset($data['result_text'])) {
    $result_text = trim($data['result_text']);
    // Buscar el WOD correspondiente
    $stmt = $pdo->prepare('SELECT id FROM wods WHERE board_id = ? AND date = ?');
    $stmt->execute([$board_id, $date]);
    $wod_id = $stmt->fetchColumn();
    if (!$wod_id) {
        echo json_encode(['status' => 'error', 'message' => 'No existe un WOD para esta fecha']);
        exit;
    }
    // Insertar el resultado (no sobrescribe)
    $stmt = $pdo->prepare('INSERT INTO results (wod_id, user_id, result_text, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$wod_id, $user_id, $result_text]);
    echo json_encode(['status' => 'success']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'No se especificó ninguna acción válida']);
