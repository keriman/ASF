<?php
// Deshabilitar la salida de errores PHP
error_reporting(0);
ini_set('display_errors', 0);

// Configurar el tipo de contenido como JSON desde el inicio
header('Content-Type: application/json');

try {
    // Incluir la configuración de la base de datos
    require_once '../../conexiones/database.php';
    
    // Verificar si se recibió el parámetro product
    if (!isset($_GET['product'])) {
        throw new Exception('Parámetro product no proporcionado');
    }
    
    // Conectar a la base de datos
    $conectar = mysql_connect($host, $user, $clave);
    if (!$conectar) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Seleccionar la base de datos
    if (!mysql_select_db($datbase, $conectar)) {
        throw new Exception('Error seleccionando la base de datos');
    }
    
    // Establecer la codificación
    mysql_set_charset('utf8', $conectar);
    
    // Escapar el valor del producto
    $product_name = mysql_real_escape_string($_GET['product']);
    
    // Realizar la consulta
    $query = "SELECT description FROM products WHERE name = '$product_name' LIMIT 1";
    $result = mysql_query($query);
    
    if (!$result) {
        throw new Exception('Error en la consulta');
    }
    
    if (mysql_num_rows($result) === 0) {
        echo json_encode([
            'success' => false,
            'description' => '',
            'message' => 'Producto no encontrado'
        ]);
    } else {
        $row = mysql_fetch_assoc($result);
        echo json_encode([
            'success' => true,
            'description' => $row['description']
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Cerrar la conexión si existe
if (isset($conectar)) {
    mysql_close($conectar);
}
?>