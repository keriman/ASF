<?php
// Configuración de la conexión a la base de datos
$host = 'localhost'; // Cambia esto por tu servidor de base de datos
$dbname = 'agrosant_pedidos'; // Cambia esto por el nombre de tu base de datos
$username = 'root'; // Cambia esto por tu usuario de base de datos
$password = ''; // Cambia esto por tu contraseña de base de datos

try {
    // Crear una nueva instancia de PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // Establecer el modo de error de PDO a EXCEPTION
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Mensaje opcional para verificar la conexión (puedes comentarlo en producción)
    // echo "Conexión exitosa a la base de datos.";
} catch (PDOException $e) {
    // Mostrar un mensaje de error si falla la conexión
    die("Error de conexión: " . $e->getMessage());
}
?>
