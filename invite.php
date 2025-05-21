<?php
// invite.php: Genera un enlace/código de invitación para una pizarra (solo admin)
// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Captura cualquier excepción global
set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode(['error' => 'Excepción: ' . $e->getMessage()]);
    exit;
});

session_start();
header('Content-Type: application/json');
require_once 'db.php';
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}
$user_id = $_SESSION['user_id'];
$board_id = intval($_POST['board_id'] ?? 0);
$email = trim($_POST['email'] ?? '');
if (!$board_id || !$email) {
    http_response_code(400);
    echo json_encode(['error' => 'Pizarra y email requeridos']);
    exit;
}
// Verifica que el usuario sea admin
$stmt = $pdo->prepare('SELECT role FROM board_members WHERE board_id = ? AND user_id = ?');
$stmt->execute([$board_id, $user_id]);
$role = $stmt->fetchColumn();
if ($role !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Solo el admin puede invitar']);
    exit;
}
// ¿Ya hay una invitación pendiente para este board y admin?
$stmt = $pdo->prepare('SELECT invite_code FROM board_members WHERE board_id = ? AND invited_by = ? AND user_id IS NULL AND role = "member" AND invite_code IS NOT NULL');
$stmt->execute([$board_id, $user_id]);
$existing_code = $stmt->fetchColumn();
if ($existing_code) {
    $code = $existing_code;
} else {
    $code = bin2hex(random_bytes(8));
    $stmt = $pdo->prepare('INSERT INTO board_members (board_id, user_id, role, invited_by, invite_code) VALUES (?, NULL, "member", ?, ?)');
    $stmt->execute([$board_id, $user_id, $code]);
}
$link = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/join.html?code=$code";

// ENVIAR EMAIL CON PHPMailer
require_once __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-master/src/SMTP.php';
require_once __DIR__ . '/PHPMailer-master/src/Exception.php';
$config = require __DIR__ . '/mail_config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = $config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['username'];
    $mail->Password = $config['password'];
    $mail->SMTPSecure = $config['encryption'];
    $mail->Port = $config['port'];
    $mail->CharSet = 'UTF-8';
    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($email);
    $mail->Subject = 'Invitación a unirte a una pizarra de CrossFit';
    $mail->isHTML(true);
    $mail->Body = "<p>¡Te han invitado a unirte a una pizarra de CrossFit!</p><p>Haz clic en el siguiente enlace para aceptar la invitación:</p><p><a href='$link'>$link</a></p><p>Si no puedes hacer clic, copia y pega el enlace en tu navegador.</p>";
    $mail->AltBody = "Te han invitado a unirte a una pizarra de CrossFit. Enlace: $link";
    $mail->send();
    echo json_encode(['success' => true, 'invite_link' => $link, 'code' => $code]);
} catch (Exception $e) {
    echo json_encode(['error' => 'No se pudo enviar el email: ' . $mail->ErrorInfo]);
}
