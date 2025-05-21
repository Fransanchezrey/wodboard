<?php
// session.php: Devuelve el usuario autenticado (si existe)
header('Content-Type: application/json');
session_start();
if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'logged_in' => true,
        'user_id' => $_SESSION['user_id'],
        'email' => $_SESSION['email'],
        'name' => $_SESSION['name']
    ]);
} else {
    echo json_encode(['logged_in' => false]);
}
?>
