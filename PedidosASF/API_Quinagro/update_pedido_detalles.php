<?php
// Archivo: update_pedido_detalles.php
// Crear archivo de log para depuración
$log_file = __DIR__ . '/update_pedido_detalles_' . date('Y-m-d') . '.log';
file_put_contents($log_file, "=== NUEVA PETICIÓN: " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
file_put_contents($log_file, "POST: " . print_r($_POST, true) . "\n", FILE_APPEND);

// Configurar cabeceras
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

date_default_timezone_set('America/Mexico_City');

// Incluir archivo de conexión
include '../../pages/conexion.php';

$response = array();

try {
    // Obtener parámetros
    $folio = isset($_POST['folio']) ? trim($_POST['folio']) : '';
    $cliente = isset($_POST['cliente']) ? trim($_POST['cliente']) : '';
    $destino = isset($_POST['destino']) ? trim($_POST['destino']) : '';
    $ruta = isset($_POST['ruta']) ? trim($_POST['ruta']) : '';
    $fecha_salida = isset($_POST['fecha_salida']) ? trim($_POST['fecha_salida']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    
    // Validar datos requeridos
    if (empty($folio)) {
        throw new Exception("Folio requerido");
    }
    
    file_put_contents($log_file, "Folio: $folio, Cliente: $cliente, Destino: $destino, Ruta: $ruta, Fecha: $fecha_salida\n", FILE_APPEND);
    
    // Formato de fecha para MySQL
    if (!empty($fecha_salida)) {
        // Convertir formato dd/mm/yyyy a yyyy-mm-dd
        $partes_fecha = explode('/', $fecha_salida);
        if (count($partes_fecha) == 3) {
            $fecha_salida = $partes_fecha[2] . '-' . $partes_fecha[1] . '-' . $partes_fecha[0];
        } else {
            $timestamp = strtotime($fecha_salida);
            if ($timestamp !== false) {
                $fecha_salida = date('Y-m-d', $timestamp);
            }
        }
    }
    
    // Verificar tipo de conexión
    if (isset($pdo) && $pdo instanceof PDO) {
        file_put_contents($log_file, "Usando conexión PDO\n", FILE_APPEND);
        
        // Actualizar pedido - sin la columna ultimo_modificador
        $sql = "UPDATE adm_pedidos SET 
                cliente = :cliente,
                destino = :destino,
                ruta = :ruta,
                fecha_salida = :fecha_salida
                WHERE folio = :folio";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':cliente', $cliente);
        $stmt->bindParam(':destino', $destino);
        $stmt->bindParam(':ruta', $ruta);
        $stmt->bindParam(':fecha_salida', $fecha_salida);
        $stmt->bindParam(':folio', $folio);
        
        $result = $stmt->execute();
        
        if ($result) {
            // Registrar la modificación en observaciones
            $observacion = "[APP] Pedido modificado por: $username - Actualización de información general";
            $sqlObs = "INSERT INTO obs_pedidos (folio, observaciones, usuario) VALUES (:folio, :observacion, :usuario)";
            $stmtObs = $pdo->prepare($sqlObs);
            $stmtObs->bindParam(':folio', $folio);
            $stmtObs->bindParam(':observacion', $observacion);
            $stmtObs->bindParam(':usuario', $username);
            $stmtObs->execute();
            
            $response = array(
                'success' => true,
                'message' => 'Pedido actualizado correctamente'
            );
        } else {
            throw new Exception("Error al actualizar el pedido");
        }
        
    } else {
        file_put_contents($log_file, "Usando conexión MySQLi\n", FILE_APPEND);
        
        // Obtener variables de conexión
        $host_var = isset($host) ? $host : 'localhost';
        $user_var = isset($username) ? $username : 'root';
        $pass_var = isset($password) ? $password : '';
        $db_var = isset($dbname) ? $dbname : 'agrosant_pedidos';
        
        // Conectar usando MySQLi
        $mysqli = new mysqli($host_var, $user_var, $pass_var, $db_var);
        
        if ($mysqli->connect_error) {
            throw new Exception("Error de conexión: " . $mysqli->connect_error);
        }
        
        $mysqli->set_charset('utf8');
        
        // Escapar valores
        $folio_safe = $mysqli->real_escape_string($folio);
        $cliente_safe = $mysqli->real_escape_string($cliente);
        $destino_safe = $mysqli->real_escape_string($destino);
        $ruta_safe = $mysqli->real_escape_string($ruta);
        $fecha_salida_safe = $mysqli->real_escape_string($fecha_salida);
        $username_safe = $mysqli->real_escape_string($username);
        
        // Actualizar pedido - sin la columna ultimo_modificador
        $sql = "UPDATE adm_pedidos SET 
                cliente = '$cliente_safe',
                destino = '$destino_safe',
                ruta = '$ruta_safe',
                fecha_salida = '$fecha_salida_safe'
                WHERE folio = '$folio_safe'";
        
        file_put_contents($log_file, "SQL: $sql\n", FILE_APPEND);
        $result = $mysqli->query($sql);
        
        if ($result) {
            // Registrar la modificación en observaciones
            $observacion = "[APP] Pedido modificado por: $username - Actualización de información general";
            $observacion_safe = $mysqli->real_escape_string($observacion);
            
            $sqlObs = "INSERT INTO obs_pedidos (folio, observaciones, usuario) VALUES ('$folio_safe', '$observacion_safe', '$username_safe')";
            $mysqli->query($sqlObs);
            
            $response = array(
                'success' => true,
                'message' => 'Pedido actualizado correctamente'
            );
        } else {
            throw new Exception("Error al actualizar el pedido: " . $mysqli->error);
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

// Devolver respuesta JSON
$json_response = json_encode($response);
file_put_contents($log_file, "Respuesta: $json_response\n", FILE_APPEND);
file_put_contents($log_file, "=== FIN DE PETICIÓN ===\n\n", FILE_APPEND);

echo $json_response;
?>