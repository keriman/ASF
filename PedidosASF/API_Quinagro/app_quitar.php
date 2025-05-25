<?php
session_start();
// Simulamos la sesión para la app móvil
if (!isset($_SESSION['username'])) {
    $_SESSION['username'] = 'oficina';
    $_SESSION['userid'] = '999';
    $_SESSION['tipo'] = 'oficina';
}

// Configurar archivo de log para depuración
$log_file = __DIR__ . '/app_quitar_' . date('Y-m-d') . '.log';
file_put_contents($log_file, "=== NUEVA PETICIÓN: " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
file_put_contents($log_file, "GET: " . print_r($_GET, true) . "\n", FILE_APPEND);
file_put_contents($log_file, "SESSION: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

date_default_timezone_set('America/Mexico_City');
include '../../pages/conexion.php';

// Registrar variables disponibles después de incluir conexion.php
file_put_contents($log_file, "Variables de conexión disponibles:\n", FILE_APPEND);
file_put_contents($log_file, "- host: " . (isset($host) ? $host : 'no disponible') . "\n", FILE_APPEND);
file_put_contents($log_file, "- username: " . (isset($username) ? 'disponible' : 'no disponible') . "\n", FILE_APPEND);
file_put_contents($log_file, "- dbname: " . (isset($dbname) ? $dbname : 'no disponible') . "\n", FILE_APPEND);
file_put_contents($log_file, "- pdo: " . (isset($pdo) ? 'disponible' : 'no disponible') . "\n", FILE_APPEND);

$response = array();

try {
    // Verificar si los parámetros necesarios están presentes
    if (isset($_GET['ff']) && isset($_GET['pp']) && isset($_GET['cc'])) {
        $folio = $_GET['ff'];
        $producto = $_GET['pp'];
        $cantidad = $_GET['cc'];
        $usuario = $_SESSION["username"];
        
        file_put_contents($log_file, "Intentando quitar producto - Folio: $folio, Producto: $producto, Cantidad: $cantidad, Usuario: $usuario\n", FILE_APPEND);
        
        // Verificar qué tipo de conexión usar (PDO o MySQLi)
        if (isset($pdo) && $pdo instanceof PDO) {
            file_put_contents($log_file, "Usando conexión PDO\n", FILE_APPEND);
            
            // Buscar el ID del producto en la tabla de pedidos usando PDO
            $buscar = "SELECT id FROM prc_pedidos 
                      WHERE folio = :folio 
                      AND producto = :producto 
                      AND cantidad = :cantidad 
                      AND procesado = 0 
                      LIMIT 1";
            
            $stmt = $pdo->prepare($buscar);
            $stmt->bindParam(':folio', $folio);
            $stmt->bindParam(':producto', $producto);
            $stmt->bindParam(':cantidad', $cantidad);
            $stmt->execute();
            
            file_put_contents($log_file, "Consulta ejecutada: " . $buscar . " (con parámetros)\n", FILE_APPEND);
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $id = $row['id'];
                file_put_contents($log_file, "Producto encontrado, ID: $id\n", FILE_APPEND);
                
                // Marcar el producto como eliminado (-1)
                $sql = "UPDATE prc_pedidos 
                        SET procesado = -1, usuario = :usuario 
                        WHERE id = :id AND folio = :folio AND procesado = 0";
                
                $update_stmt = $pdo->prepare($sql);
                $update_stmt->bindParam(':usuario', $usuario);
                $update_stmt->bindParam(':id', $id);
                $update_stmt->bindParam(':folio', $folio);
                $result = $update_stmt->execute();
                
                file_put_contents($log_file, "Actualización ejecutada: " . $sql . " (con parámetros)\n", FILE_APPEND);
                
                if ($result) {
                    file_put_contents($log_file, "Producto eliminado correctamente\n", FILE_APPEND);
                    $response['success'] = true;
                    $response['message'] = "Producto eliminado correctamente";
                } else {
                    $error = implode(", ", $update_stmt->errorInfo());
                    file_put_contents($log_file, "Error al actualizar el registro: $error\n", FILE_APPEND);
                    $response['success'] = false;
                    $response['error'] = "Error al actualizar el registro: $error";
                }
            } else {
                file_put_contents($log_file, "No se encontró el producto en el pedido\n", FILE_APPEND);
                $response['success'] = false;
                $response['error'] = "No se encontró el producto en el pedido";
            }
            
        } else {
            file_put_contents($log_file, "Usando conexión MySQLi alternativa\n", FILE_APPEND);
            
            // Obtener las variables de conexión correctas o usar valores por defecto
            $host_var = isset($host) ? $host : 'localhost';
            $user_var = isset($username) ? $username : 'root';
            $pass_var = isset($password) ? $password : '';
            $db_var = isset($dbname) ? $dbname : 'agrosant_pedidos';
            
            file_put_contents($log_file, "Conectando con: host=$host_var, user=$user_var, db=$db_var\n", FILE_APPEND);
            
            // Conectar usando MySQLi
            $mysqli = new mysqli($host_var, $user_var, $pass_var, $db_var);
            
            if ($mysqli->connect_error) {
                throw new Exception("Error de conexión a la base de datos: " . $mysqli->connect_error);
            }
            
            $mysqli->set_charset('utf8');
            
            // Escapar para prevenir inyección SQL
            $folio_safe = $mysqli->real_escape_string($folio);
            $producto_safe = $mysqli->real_escape_string($producto);
            $cantidad_safe = $mysqli->real_escape_string($cantidad);
            $usuario_safe = $mysqli->real_escape_string($usuario);
            
            // Buscar el ID del producto en la tabla de pedidos
            $buscar = "SELECT id FROM prc_pedidos 
                      WHERE folio = '$folio_safe' 
                      AND producto = '$producto_safe' 
                      AND cantidad = '$cantidad_safe' 
                      AND procesado = 0 
                      LIMIT 1";
            
            file_put_contents($log_file, "Consulta ejecutada: $buscar\n", FILE_APPEND);
            $resultado = $mysqli->query($buscar);
            
            if (!$resultado) {
                throw new Exception("Problemas en la consulta: " . $mysqli->error);
            }
            
            if ($resultado->num_rows > 0) {
                $row = $resultado->fetch_assoc();
                $id = $row['id'];
                file_put_contents($log_file, "Producto encontrado, ID: $id\n", FILE_APPEND);
                
                // Marcar el producto como eliminado (-1)
                $sql = "UPDATE prc_pedidos SET procesado=-1, usuario='$usuario_safe' WHERE id=$id AND folio='$folio_safe' AND procesado=0";
                file_put_contents($log_file, "Actualización ejecutada: $sql\n", FILE_APPEND);
                $result = $mysqli->query($sql);
                
                if ($result) {
                    file_put_contents($log_file, "Producto eliminado correctamente\n", FILE_APPEND);
                    $response['success'] = true;
                    $response['message'] = "Producto eliminado correctamente";
                } else {
                    $error = $mysqli->error;
                    file_put_contents($log_file, "Error al actualizar el registro: $error\n", FILE_APPEND);
                    $response['success'] = false;
                    $response['error'] = "Error al actualizar el registro: $error";
                }
            } else {
                file_put_contents($log_file, "No se encontró el producto en el pedido\n", FILE_APPEND);
                $response['success'] = false;
                $response['error'] = "No se encontró el producto en el pedido";
            }
            
            // Cerrar la conexión MySQLi
            $mysqli->close();
        }
    } else {
        file_put_contents($log_file, "Parámetros incompletos\n", FILE_APPEND);
        $response['success'] = false;
        $response['error'] = "Parámetros incompletos";
    }
} catch (Exception $e) {
    $error_msg = $e->getMessage();
    file_put_contents($log_file, "Excepción: $error_msg\n", FILE_APPEND);
    $response['success'] = false;
    $response['error'] = $error_msg;
}

// Registrar respuesta
file_put_contents($log_file, "Respuesta: " . json_encode($response) . "\n", FILE_APPEND);
file_put_contents($log_file, "=== FIN DE PETICIÓN ===\n\n", FILE_APPEND);

// Devolver respuesta en formato JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://127.0.0.1');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Max-Age: 3600');

echo json_encode($response);
?>