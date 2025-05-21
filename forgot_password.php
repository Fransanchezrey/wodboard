<?php
// forgot_password.php: genera un token y envía email de recuperación
header('Content-Type: application/json');
require_once 'db.php';
require_once 'PHPMailer-master/src/PHPMailer.php';
require_once 'PHPMailer-master/src/SMTP.php';
require_once 'PHPMailer-master/src/Exception.php';
$config = require __DIR__ . '/mail_config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$email = trim($_POST['email'] ?? '');
if (!$email) {
    echo json_encode(['error' => 'Email requerido']);
    exit;
}
// Buscar usuario
$stmt = $pdo->prepare('SELECT id, name FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo json_encode(['error' => 'Si el email existe, recibirás instrucciones.']); // Mensaje neutro
    exit;
}
// Generar token seguro
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', time() + 3600); // 1h
// Guardar token en tabla recovery (crear si no existe)
$pdo->exec('CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    INDEX(token),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)');
$stmt = $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
$stmt->execute([$user['id'], $token, $expires]);
$link = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.html?token=$token";
// Enviar email
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
    $mail->addAddress($email, $user['name']);
    $mail->Subject = 'Recupera tu contraseña de Pizarra Virtual';
    $mail->isHTML(true);
    $mail->Body = "<p>Hola, {$user['name']}:</p><p>Haz clic en el siguiente enlace para restablecer tu contraseña:</p><p><a href='$link'>$link</a></p><p>Este enlace es válido durante 1 hora.</p>";
    $mail->AltBody = "Haz clic en el enlace para restablecer tu contraseña: $link (válido 1h)";
    $mail->send();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'No se pudo enviar el email: ' . $mail->ErrorInfo]);
}
