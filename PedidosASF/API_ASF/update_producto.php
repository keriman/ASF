<?php
// Archivo: update_producto.php
// Actualizar un producto específico

// Configurar archivo de log
$log_file = __DIR__ . '/update_producto_' . date('Y-m-d') . '.log';
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
    $producto = isset($_POST['producto']) ? trim($_POST['producto']) : '';
    $presentacion = isset($_POST['presentacion']) ? trim($_POST['presentacion']) : '';
    $cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 0;
    $folio = isset($_POST['folio']) ? trim($_POST['folio']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    
    // Validar datos requeridos
    if ($id <= 0 || empty($folio)) {
        throw new Exception("ID y folio son requeridos");
    }
    
    if ($cantidad < 0) {
        throw new Exception("La cantidad no puede ser negativa");
    }
    
    file_put_contents($log_file, "ID: $id, Cantidad: $cantidad, Folio: $folio\n", FILE_APPEND);
    
    // Decidir qué conexión usar
    if (isset($pdo) && $pdo instanceof PDO) {
        file_put_contents($log_file, "Usando conexión PDO\n", FILE_APPEND);
        
        // Actualizar producto
        $sql = "UPDATE prc_pedidos 
                SET producto = :producto,
                    presentacion = :presentacion,
                    cantidad = :cantidad,
                    usuario = :username,
                    procesado = 0
                WHERE id = :id AND folio = :folio";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':producto', $producto);
        $stmt->bindParam(':presentacion', $presentacion);
        $stmt->bindParam(':cantidad', $cantidad);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':folio', $folio);
        
        $result = $stmt->execute();
        
        if ($result) {
            // Registrar la acción
            $accion = "[APP] Producto actualizado - $producto: cantidad cambiada a $cantidad";
            $sqlObs = "INSERT INTO obs_pedidos (folio, observaciones, usuario) VALUES (:folio, :observacion, :usuario)";
            $stmtObs = $pdo->prepare($sqlObs);
            $stmtObs->bindParam(':folio', $folio);
            $stmtObs->bindParam(':observacion', $accion);
            $stmtObs->bindParam(':usuario', $username);
            $stmtObs->execute();
            
            $response = array(
                'success' => true,
                'message' => 'Producto actualizado correctamente'
            );
        } else {
            throw new Exception("Error al actualizar el producto");
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
        $producto_safe = $mysqli->real_escape_string($producto);
        $presentacion_safe = $mysqli->real_escape_string($presentacion);
        $cantidad_safe = intval($cantidad);
        $folio_safe = $mysqli->real_escape_string($folio);
        $username_safe = $mysqli->real_escape_string($username);
        
        // Actualizar producto
        $sql = "UPDATE prc_pedidos 
                SET producto = '$producto_safe',
                    presentacion = '$presentacion_safe',
                    cantidad = $cantidad_safe,
                    usuario = '$username_safe',
                    procesado = 0
                WHERE id = $id_safe AND folio = '$folio_safe'";
        
        file_put_contents($log_file, "SQL: $sql\n", FILE_APPEND);
        $result = $mysqli->query($sql);
        
        if ($result) {
            // Registrar la acción
            $accion = "[APP] Producto actualizado - $producto: cantidad cambiada a $cantidad";
            $accion_safe = $mysqli->real_escape_string($accion);
            
            $sqlObs = "INSERT INTO obs_pedidos (folio, observaciones, usuario) VALUES ('$folio_safe', '$accion_safe', '$username_safe')";
            $mysqli->query($sqlObs);
            
            $response = array(
                'success' => true,
                'message' => 'Producto actualizado correctamente'
            );
        } else {
            throw new Exception("Error al actualizar el producto: " . $mysqli->error);
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