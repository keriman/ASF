<?php
header('Content-Type: application/json');

include '../../conexiones/database.php';

// Verificar si se proporcionó un producto
if (!isset($_GET['product']) || empty($_GET['product'])) {
    echo json_encode(['success' => false, 'message' => 'No se proporcionó un producto']);
    exit;
}

$product = $_GET['product'];

// Conectar a la base de datos
$conectar = mysql_connect($host, $user, $clave);
mysql_select_db($datbase, $conectar);
mysql_set_charset('utf8', $conectar);

// Buscar el producto
$query = "SELECT description FROM products WHERE name = '" . mysql_real_escape_string($product) . "' LIMIT 1";
$result = mysql_query($query);

if ($result && mysql_num_rows($result) > 0) {
    $row = mysql_fetch_assoc($result);
    echo json_encode(['success' => true, 'description' => $row['description']]);
} else {
    echo json_encode(['success' => false, 'error' => 'Producto no encontrado']);
}

mysql_close($conectar);