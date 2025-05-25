<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php-error.log');

// Permitir acceso CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

// Función para limpiar entradas
function limpiar($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Crear array para información de depuración
$debug_info = [];
$debug_info[] = "Script iniciado";

try {
    // Incluir archivo de configuración de la base de datos
    include '../conexiones/database.php';
    $debug_info[] = "Archivo de conexión incluido";

    // Verificar que se proporcionaron los parámetros necesarios
    if (!isset($_GET['folio']) || !isset($_GET['username'])) {
        throw new Exception("Faltan parámetros requeridos (folio y username)");
    }

    // Obtener y limpiar los parámetros
    $folio = limpiar($_GET['folio']);
    $username = limpiar($_GET['username']);
    $debug_info[] = "Parámetros: folio=$folio, username=$username";

    // Verificar que el folio sea un número
    if (!is_numeric($folio)) {
        throw new Exception("El folio debe ser un número");
    }

    // Establecer la conexión a la base de datos
    $conn = mysqli_connect($host, $user, $clave, $datbase);
    if (!$conn) {
        throw new Exception("Error de conexión a la base de datos: " . mysqli_connect_error());
    }
    
    // Establecer el charset a UTF-8
    mysqli_set_charset($conn, "utf8");
    $debug_info[] = "Conexión a la base de datos establecida con charset UTF-8";

    // Test específico para el producto problemático
    $test_sql = "SELECT * FROM prc_pedidos WHERE producto = 'tecnogreen 1 kg' AND folio = 4035017";
    $test_result = mysqli_query($conn, $test_sql);
    if (!$test_result) {
        $debug_info[] = "Error consulta de producto específico: " . mysqli_error($conn);
    } else {
        $prod_test = mysqli_fetch_assoc($test_result);
        if ($prod_test) {
            $debug_info[] = "Datos del producto problemático encontrados";
        } else {
            $debug_info[] = "Producto problemático no encontrado con consulta directa";
        }
    }

    // 1. Obtener información del pedido
    $sql_pedido = "SELECT folio, vendedor, destino, fecha_salida, status, cliente, ruta 
                  FROM adm_pedidos 
                  WHERE folio = " . mysqli_real_escape_string($conn, $folio);
    $debug_info[] = "SQL Pedido: $sql_pedido";
    
    $result_pedido = mysqli_query($conn, $sql_pedido);
    if (!$result_pedido) {
        throw new Exception("Error en la consulta del pedido: " . mysqli_error($conn));
    }
    
    if (mysqli_num_rows($result_pedido) === 0) {
        throw new Exception("No se encontró ningún pedido con el folio proporcionado");
    }
    
    $pedido = mysqli_fetch_assoc($result_pedido);
    $debug_info[] = "Pedido encontrado: " . json_encode($pedido);

    // 2. Obtener los productos del pedido
    $sql_productos = "SELECT id, producto, presentacion, cantidad, procesado 
                     FROM prc_pedidos 
                     WHERE folio = " . mysqli_real_escape_string($conn, $folio);
    $debug_info[] = "SQL Productos: $sql_productos";
    
    $result_productos = mysqli_query($conn, $sql_productos);
    if (!$result_productos) {
        throw new Exception("Error en la consulta de productos: " . mysqli_error($conn));
    }
    
    $productos = [];
    while ($row = mysqli_fetch_assoc($result_productos)) {
        // Convertir el campo procesado a boolean para JSON
        $row['procesado'] = (bool)$row['procesado'];
        // Asegurarse de que ningún valor es NULL
        foreach ($row as $key => $value) {
            if ($value === NULL) {
                $row[$key] = "";
            }
        }
        $productos[] = $row;
    }
    $debug_info[] = "Productos encontrados: " . count($productos);

    // 3. Obtener las observaciones del pedido
    $sql_observaciones = "SELECT id, observaciones, usuario, FR, modificada 
                         FROM obs_pedidos 
                         WHERE folio = " . mysqli_real_escape_string($conn, $folio);
    $debug_info[] = "SQL Observaciones: $sql_observaciones";
    
    $result_observaciones = mysqli_query($conn, $sql_observaciones);
    if (!$result_observaciones) {
        throw new Exception("Error en la consulta de observaciones: " . mysqli_error($conn));
    }
    
    $observaciones = [];
    while ($row = mysqli_fetch_assoc($result_observaciones)) {
        // Convertir el campo modificada a boolean para JSON
        $row['modificada'] = (bool)$row['modificada'];
        // Asegurarse de que ningún valor es NULL
        foreach ($row as $key => $value) {
            if ($value === NULL) {
                $row[$key] = "";
            }
        }
        $observaciones[] = $row;
    }
    $debug_info[] = "Observaciones encontradas: " . count($observaciones);

    // Preparar la respuesta
    $detalles_pedido = [
        "pedido" => $pedido,
        "productos" => $productos,
        "observaciones" => $observaciones
    ];
    
    $response = [
        "error" => false,
        "message" => "",
        "debug_info" => $debug_info,
        "data" => $detalles_pedido
    ];
    
    // Verificar si hay errores de JSON antes de enviarlo
    $json_response = json_encode($response, JSON_UNESCAPED_UNICODE);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Error al codificar JSON: " . json_last_error_msg());
    }
    
    // Devolver la respuesta en formato JSON
    echo $json_response;
    
    // Cerrar la conexión
    mysqli_close($conn);
    
} catch (Exception $e) {
    // En caso de error, devolver una respuesta JSON con el mensaje de error
    $response = [
        "error" => true,
        "message" => $e->getMessage(),
        "debug_info" => $debug_info,
        "data" => null
    ];
    
    // Verificar si hay errores de JSON antes de enviarlo
    $json_response = json_encode($response, JSON_UNESCAPED_UNICODE);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Si hay un error de JSON, enviar una respuesta de error simple
        echo '{"error":true,"message":"Error al codificar JSON: ' . json_last_error_msg() . '"}';
    } else {
        // Devolver la respuesta en formato JSON
        echo $json_response;
    }
    
    // Cerrar la conexión si existe
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?>