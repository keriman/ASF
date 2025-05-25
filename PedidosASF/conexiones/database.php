<?php
// Log para depuración
$db_log_file = __DIR__ . '/database_' . date('Y-m-d') . '.log';
file_put_contents($db_log_file, "== DATABASE.PHP INCLUIDO: " . date('Y-m-d H:i:s') . " ==\n", FILE_APPEND);
file_put_contents($db_log_file, "Desde: " . $_SERVER['SCRIPT_FILENAME'] . "\n", FILE_APPEND);

// Datos de la conexión a la base de datos
$host = "localhost"; // servidor
$user = "root"; // usuario de la base de datos
$clave = ""; //contraseña del usuario de la base de datos
$datbase = "agrosant_pedidos"; // nombre de la base de datos

// Verificar y registrar valores en el log
file_put_contents($db_log_file, "Host: $host\nUser: $user\nDB: $datbase\n", FILE_APPEND);
file_put_contents($db_log_file, "== FIN DATABASE.PHP ==\n\n", FILE_APPEND);
?>