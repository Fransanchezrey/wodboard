<?php
require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');

// Permitir board_id por GET o POST
$board_id = null;
if (isset($_GET['board_id'])) {
    $board_id = intval($_GET['board_id']);
} elseif (isset($_POST['board_id'])) {
    $board_id = intval($_POST['board_id']);
} elseif (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['board_id'])) {
        $board_id = intval($data['board_id']);
    }
}
if (!$board_id) {
    echo json_encode([]);
    exit;
}

// Obtener todos los WODs para el board
$stmt = $pdo->prepare('SELECT w.id, w.date, w.wod_text FROM wods w WHERE w.board_id = ?');
$stmt->execute([$board_id]);
$wods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Preparar estructura de respuesta
$response = [];
foreach ($wods as $wod) {
    $response[$wod['date']] = [
        'wod' => $wod['wod_text'],
        'results' => []
    ];
}

// Obtener todos los resultados de esos WODs
if (count($wods) > 0) {
    $wod_ids = array_column($wods, 'id');
    $in = str_repeat('?,', count($wod_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT r.wod_id, r.result_text, r.user_id, u.name FROM results r JOIN users u ON r.user_id = u.id WHERE r.wod_id IN ($in) ORDER BY r.id ASC");
    $stmt->execute($wod_ids);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $res) {
        // Buscar la fecha correspondiente a este wod_id
        foreach ($wods as $wod) {
            if ($wod['id'] == $res['wod_id']) {
                $response[$wod['date']]['results'][] = [
                    'name' => $res['name'],
                    'result' => $res['result_text'],
                    'user_id' => intval($res['user_id'])
                ];
                break;
            }
        }
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
