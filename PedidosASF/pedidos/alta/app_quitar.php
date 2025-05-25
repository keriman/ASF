<?php
session_start();
// Simulamos la sesión para la app móvil
if (!isset($_SESSION['username'])) {
    $_SESSION['username'] = 'oficina';
    $_SESSION['userid'] = '999';
    $_SESSION['tipo'] = 'oficina';
}

date_default_timezone_set('America/Mexico_City');
include '../../conexiones/database.php';

$conectar = mysql_connect($host, $user, $clave);
mysql_select_db($datbase, $conectar);
mysql_set_charset('utf8', $conectar);

$response = array();

// Verificar si los parámetros necesarios están presentes
if (isset($_GET['ff']) && isset($_GET['pp']) && isset($_GET['cc'])) {
    $folio = $_GET['ff'];
    $producto = $_GET['pp'];
    $cantidad = $_GET['cc'];
    $usuario = $_SESSION["username"];

    // Buscar el ID del producto en la tabla de pedidos
    $buscar = "SELECT id FROM prc_pedidos 
              WHERE folio = '$folio' 
              AND producto = '$producto' 
              AND cantidad = '$cantidad' 
              AND procesado = 0 
              LIMIT 1";
    
    $resultado = mysql_query($buscar) or die("Problemas en la consulta: " . mysql_error());
    
    if (mysql_num_rows($resultado) > 0) {
        $row = mysql_fetch_array($resultado);
        $id = $row['id'];
        
        // Marcar el producto como eliminado (-1)
        $sql = "UPDATE prc_pedidos SET procesado=-1, usuario='$usuario' WHERE id=$id AND folio='$folio' AND procesado=0";
        $result = mysql_query($sql);
        
        if ($result) {
            $response['success'] = true;
            $response['message'] = "Producto eliminado correctamente";
        } else {
            $response['success'] = false;
            $response['error'] = "Error al actualizar el registro: " . mysql_error();
        }
    } else {
        $response['success'] = false;
        $response['error'] = "No se encontró el producto en el pedido";
    }
} else {
    $response['success'] = false;
    $response['error'] = "Parámetros incompletos";
}

// Devolver respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode($response);

mysql_close($conectar);
?>