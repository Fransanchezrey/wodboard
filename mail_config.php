<?php
// Configuración SMTP para PHPMailer con Gmail
// ¡IMPORTANTE! Usa una "contraseña de aplicación" de Gmail, no tu contraseña normal.
// Más info: https://support.google.com/accounts/answer/185833

return [
    'host' => 'smtp.gmail.com',
    'username' => 'franciscosanchezrey@gmail.com',
    'password' => 'yywfgztbljcjmruk',
    'port' => 587,
    'encryption' => 'tls',
    'from_email' => 'franciscosanchezrey@gmail.com',
    'from_name' => 'Pizarra Virtual',
];
