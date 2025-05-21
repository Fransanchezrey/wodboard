<?php
// register.php: registro de nuevos usuarios
header('Content-Type: application/json');
require_once 'db.php';
session_start();

// Recoge datos del POST
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$name = trim($_POST['name'] ?? '');

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Email y contraseña requeridos']);
    exit;
}

// Comprueba si ya existe el usuario
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => 'El email ya está registrado']);
    exit;
}

// Hashea la contraseña
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('INSERT INTO users (email, password_hash, name) VALUES (?, ?, ?)');
$stmt->execute([$email, $hash, $name]);
$user_id = $pdo->lastInsertId();

// Autologin tras registro
$_SESSION['user_id'] = $user_id;
$_SESSION['email'] = $email;
$_SESSION['name'] = $name;

// Respuesta
echo json_encode(['success' => true, 'user_id' => $user_id, 'email' => $email, 'name' => $name]);
