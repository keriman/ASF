<?php
// Archivo: delete_producto.php
// Eliminar un producto específico

// Configurar archivo de log
$log_file = __DIR__ . '/delete_producto_' . date('Y-m-d') . '.log';
file_put_contents($log_file, "=== NUEVA PETICIÓN: " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
file_put_contents($log_file, "POST: " . print_r($_POST, true) . "\n", FILE_APPEND);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

date_default_timezone_set('America/Mexico_City');
include '../../pages/conexion.php';

$response = array();

try {
    // Obtener parámetros
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $folio = isset($_POST['folio']) ? trim($_POST['folio']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    
    // Validar datos requeridos
    if ($id <= 0 || empty($folio)) {
        throw new Exception("ID y folio son requeridos");
    }
    
    file_put_contents($log_file, "ID: $id, Folio: $folio, Usuario: $username\n", FILE_APPEND);
    
    // Decidir qué conexión usar
    if (isset($pdo) && $pdo instanceof PDO) {
        file_put_contents($log_file, "Usando conexión PDO\n", FILE_APPEND);
        
        // Primero obtener información del producto para el log
        $sqlInfo = "SELECT producto, presentacion FROM prc_pedidos WHERE id = :id AND folio = :folio";
        $stmtInfo = $pdo->prepare($sqlInfo);
        $stmtInfo->bindParam(':id', $id);
        $stmtInfo->bindParam(':folio', $folio);
        $stmtInfo->execute();
        $producto_info = $stmtInfo->fetch(PDO::FETCH_ASSOC);
        
        // Marcar producto como eliminado
        $sql = "UPDATE prc_pedidos 
                SET procesado = -1,
                    usuario = :username
                WHERE id = :id AND folio = :folio";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':folio', $folio);
        
        $result = $stmt->execute();
        
        if ($result) {
            // Registrar la acción
            $producto_desc = $producto_info ? $producto_info['producto'] : 'ID: ' . $id;
            $accion = "[APP] Producto eliminado: " . $producto_desc;
            
            $sqlObs = "INSERT INTO obs_pedidos (folio, observaciones, usuario) VALUES (:folio, :observacion, :usuario)";
            $stmtObs = $pdo->prepare($sqlObs);
            $stmtObs->bindParam(':folio', $folio);
            $stmtObs->bindParam(':observacion', $accion);
            $stmtObs->bindParam(':usuario', $username);
            $stmtObs->execute();
            
            $response = array(
                'success' => true,
                'message' => 'Producto eliminado correctamente'
            );
        } else {
            throw new Exception("Error al eliminar el producto");
        }
        
    } else {
        file_put_contents($log_file, "Usando conexión MySQLi\n", FILE_APPEND);
        
        // Conectar usando MySQLi
        $host_var = isset($host) ? $host : 'localhost';
        $user_var = isset($username) ? $username : 'root';
        $pass_var = isset($password) ? $password : '';
        $db_var = isset($dbname) ? $dbname : 'agrosant_pedidos';
        
        $mysqli = new mysqli($host_var, $user_var, $pass_var, $db_var);
        
        if ($mysqli->connect_error) {
            throw new Exception("Error de conexión: " . $mysqli->connect_error);
        }
        
        $mysqli->set_charset('utf8');
        
        // Escapar valores
        $id_safe = intval($id);
        $folio_safe = $mysqli->real_escape_string($folio);
        $username_safe = $mysqli->real_escape_string($username);
        
        // Primero obtener información del producto para el log
        $sqlInfo = "SELECT producto, presentacion FROM prc_pedidos WHERE id = $id_safe AND folio = '$folio_safe'";
        $result_info = $mysqli->query($sqlInfo);
        $producto_info = $result_info ? $result_info->fetch_assoc() : null;
        
        // Marcar producto como eliminado
        $sql = "UPDATE prc_pedidos 
                SET procesado = -1,
                    usuario = '$username_safe'
                WHERE id = $id_safe AND folio = '$folio_safe'";
        
        file_put_contents($log_file, "SQL: $sql\n", FILE_APPEND);
        $result = $mysqli->query($sql);
        
        if ($result) {
            // Registrar la acción
            $producto_desc = $producto_info ? $producto_info['producto'] : 'ID: ' . $id;
            $accion = "[APP] Producto eliminado: " . $producto_desc;
            $accion_safe = $mysqli->real_escape_string($accion);
            
            $sqlObs = "INSERT INTO obs_pedidos (folio, observaciones, usuario) VALUES ('$folio_safe', '$accion_safe', '$username_safe')";
            $mysqli->query($sqlObs);
            
            $response = array(
                'success' => true,
                'message' => 'Producto eliminado correctamente'
            );
        } else {
            throw new Exception("Error al eliminar el producto: " . $mysqli->error);
        }
        
        $mysqli->close();
    }
    
} catch (Exception $e) {
    $response = array(
        'success' => false,
        'message' => $e->getMessage()
    );
    file_put_contents($log_file, "Error: " . $e->getMessage() . "\n", FILE_APPEND);
}

$json_response = json_encode($response);
file_put_contents($log_file, "Respuesta: $json_response\n", FILE_APPEND);
file_put_contents($log_file, "=== FIN DE PETICIÓN ===\n\n", FILE_APPEND);

echo $json_response;
?>