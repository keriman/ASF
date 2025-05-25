<?php
// Registrar todos los parámetros recibidos
error_log("POST: " . print_r($_POST, true));

// Configuración básica
session_start();
date_default_timezone_set('America/Mexico_City');
include '../../conexiones/database.php';

// Conexión a la base de datos
$conectar = mysql_connect($host, $user, $clave);
mysql_select_db($datbase, $conectar);
mysql_set_charset('utf8', $conectar);

// Obtener los parámetros
$folio = $_POST['ff'] ?? '';
$vendedor = $_POST['vv'] ?? '';
$cliente = $_POST['cc'] ?? ''; // Desde la app
$destino = $_POST['rr'] ?? '';
$fecha = $_POST['fs'] ?? '';
$comentarios = $_POST['cm'] ?? '';

// Preparar la respuesta
$response = array();

try {
    // Si el pedido no existe, insertarlo
    $sql = "INSERT INTO adm_pedidos (folio, vendedor, cliente, destino, fecha_salida, status, usuario) 
            VALUES ('$folio', '$vendedor', '$cliente', '$destino', '$fecha', 10, 'app')";
    
    $result = mysql_query($sql);
    if (!$result) {
        error_log("Error en SQL: " . mysql_error());
        throw new Exception("Error al insertar: " . mysql_error());
    }
    
    $response['status'] = 'success';
    $response['folio'] = $folio;
    
    // Si hay comentarios, guardarlos
    if (!empty($comentarios)) {
        $sqlComentario = "INSERT INTO obs_pedidos (folio, observaciones, usuario) 
                          VALUES ('$folio', '$comentarios', 'app')";
        mysql_query($sqlComentario);
    }
} 
catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
    error_log("Error en altas.php: " . $e->getMessage());
}

// Devolver la respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);
?>