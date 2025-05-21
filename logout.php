<?php
// logout.php: cierre de sesión
header('Content-Type: application/json');
session_start();
session_destroy();
echo json_encode(['success' => true]);
