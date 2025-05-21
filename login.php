<?php
// login.php: autenticación de usuarios
header('Content-Type: application/json');
require_once 'db.php';
session_start();

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Email y contraseña requeridos']);
    exit;
}

$stmt = $pdo->prepare('SELECT id, password_hash, name FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Credenciales incorrectas']);
    exit;
}

// Login correcto
$_SESSION['user_id'] = $user['id'];
$_SESSION['email'] = $email;
$_SESSION['name'] = $user['name'];

echo json_encode(['success' => true, 'user_id' => $user['id'], 'email' => $email, 'name' => $user['name']]);
