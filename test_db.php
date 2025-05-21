<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=pizarra_virtual;charset=utf8mb4", "root", "");
    echo "¡Conexión exitosa!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
