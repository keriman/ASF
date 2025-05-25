<?php
// Script para recibir pedidos desde la aplicación móvil
// Depuración extendida

// Crear un archivo de log específico para esta petición
$log_file = __DIR__ . '/app_save_order_' . date('Y-m-d') . '.log';
file_put_contents($log_file, "=== NUEVA PETICIÓN: " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
file_put_contents($log_file, "POST: " . print_r($_POST, true) . "\n", FILE_APPEND);

// Activar el registro de errores
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_log("Recibiendo petición de la app móvil");

// Configuración básica
date_default_timezone_set('America/Mexico_City');

// Incluir la configuración de la base de datos
include '../../pages/conexion.php';

// Registrar las variables de conexión disponibles
file_put_contents($log_file, "Variables de conexión disponibles:\n", FILE_APPEND);
file_put_contents($log_file, "- host: " . (isset($host) ? $host : 'no disponible') . "\n", FILE_APPEND);
file_put_contents($log_file, "- username: " . (isset($username) ? 'disponible' : 'no disponible') . "\n", FILE_APPEND);
file_put_contents($log_file, "- dbname: " . (isset($dbname) ? $dbname : 'no disponible') . "\n", FILE_APPEND);
file_put_contents($log_file, "- pdo: " . (isset($pdo) ? 'disponible' : 'no disponible') . "\n", FILE_APPEND);

try {
    // Obtener los parámetros
    $folio = isset($_POST['ff']) ? $_POST['ff'] : '';
    $vendedor = isset($_POST['vv']) ? $_POST['vv'] : '';  // Este es el nombre completo del vendedor
    $cliente = isset($_POST['cc']) ? $_POST['cc'] : '';
    $destino = isset($_POST['rr']) ? $_POST['rr'] : '';
    $ruta = isset($_POST['rt']) ? $_POST['rt'] : '';      // Capturar el nuevo campo ruta
    
    // Obtener y validar la fecha
    $fecha_salida = isset($_POST['fs']) ? $_POST['fs'] : '';
    file_put_contents($log_file, "Fecha recibida: '$fecha_salida'\n", FILE_APPEND);
    
    // Validar formato de fecha y convertir si es necesario
    if (!empty($fecha_salida)) {
        // Verificar si es una fecha en formato yyyy-mm-dd
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_salida)) {
            // Intentar convertir otros formatos comunes (dd/mm/yyyy, mm/dd/yyyy, etc.)
            $timestamp = strtotime($fecha_salida);
            if ($timestamp === false) {
                // Si no se puede convertir, usar la fecha actual
                $fecha_salida = date('Y-m-d');
                file_put_contents($log_file, "Formato de fecha no reconocido, usando fecha actual: '$fecha_salida'\n", FILE_APPEND);
            } else {
                $fecha_salida = date('Y-m-d', $timestamp);
                file_put_contents($log_file, "Fecha convertida a formato MySQL: '$fecha_salida'\n", FILE_APPEND);
            }
        }
    } else {
        // Si no hay fecha, usar la fecha actual
        $fecha_salida = date('Y-m-d');
        file_put_contents($log_file, "No se recibió fecha, usando fecha actual: '$fecha_salida'\n", FILE_APPEND);
    }
    
    // Obtener explícitamente el parámetro de usuario
    $usuario = isset($_POST['usuario']) ? $_POST['usuario'] : 'Quinagro';
    
    file_put_contents($log_file, "Parámetros recibidos - Vendedor: '$vendedor', Usuario: '$usuario', Ruta: '$ruta', Fecha salida: '$fecha_salida'\n", FILE_APPEND);
    
    // Obtener comentarios 
    $comentarios = '';
    if (isset($_POST['cm'])) {
        $comentarios = $_POST['cm'];
        file_put_contents($log_file, "Comentarios encontrados en 'cm': '$comentarios'\n", FILE_APPEND);
    } elseif (isset($_POST['comentarios'])) {
        $comentarios = $_POST['comentarios'];
        file_put_contents($log_file, "Comentarios encontrados en 'comentarios': '$comentarios'\n", FILE_APPEND);
    } elseif (isset($_POST['comments'])) {
        $comentarios = $_POST['comments'];
        file_put_contents($log_file, "Comentarios encontrados en 'comments': '$comentarios'\n", FILE_APPEND);
    }
    
    // Validar datos mínimos requeridos
    if (empty($folio) || empty($destino)) {
        throw new Exception("Faltan datos requeridos (folio, destino)");
    }
    
    // Validar que la ruta no esté vacía
    if (empty($ruta)) {
        file_put_contents($log_file, "Advertencia: El campo 'ruta' está vacío\n", FILE_APPEND);
    }
    
    // Limitar longitud del campo usuario en caso de error
    $usuario = substr($usuario, 0, 10);

    // Verificar qué tipo de conexión usar (PDO o MySQLi)
    if (isset($pdo) && $pdo instanceof PDO) {
        file_put_contents($log_file, "Usando conexión PDO\n", FILE_APPEND);
        
        // Primero, obtener la estructura de la tabla para identificar las columnas
        $describeTable = $pdo->query("DESCRIBE adm_pedidos");
        
        if ($describeTable === false) {
            throw new Exception("No se pudo obtener la estructura de la tabla adm_pedidos");
        }
        
        $columns = [];
        while ($row = $describeTable->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $row['Field'];
        }
        
        file_put_contents($log_file, "Columnas encontradas en adm_pedidos: " . implode(", ", $columns) . "\n", FILE_APPEND);
        
        // Construir la consulta basada en las columnas disponibles
        $fields = [];
        $placeholders = [];
        $params = [];
        
        if (in_array('folio', $columns)) {
            $fields[] = 'folio';
            $placeholders[] = ':folio';
            $params[':folio'] = $folio;
        }
        
        if (in_array('vendedor', $columns)) {
            $fields[] = 'vendedor';
            $placeholders[] = ':vendedor';
            $params[':vendedor'] = $vendedor;
        }
        
        if (in_array('destino', $columns)) {
            $fields[] = 'destino';
            $placeholders[] = ':destino';
            $params[':destino'] = $destino;
        }
        
        if (in_array('cliente', $columns)) {
            $fields[] = 'cliente';
            $placeholders[] = ':cliente';
            $params[':cliente'] = $cliente;
        }
        
        if (in_array('ruta', $columns)) {
            $fields[] = 'ruta';
            $placeholders[] = ':ruta';
            $params[':ruta'] = $ruta;
        }
        
        if (in_array('fecha_salida', $columns)) {
            $fields[] = 'fecha_salida';
            $placeholders[] = ':fecha_salida';
            $params[':fecha_salida'] = $fecha_salida;
        }
        
        if (in_array('status', $columns)) {
            $fields[] = 'status';
            $placeholders[] = ':status';
            $params[':status'] = 10; // Valor por defecto según el script original
        }
        
        if (in_array('usuario', $columns)) {
            $fields[] = 'usuario';
            $placeholders[] = ':usuario';
            $params[':usuario'] = $usuario;
        }
        
        // Solo agregar 'origen' si la columna existe
        if (in_array('origen', $columns)) {
            $fields[] = 'origen';
            $placeholders[] = ':origen';
            $params[':origen'] = 'app_movil';
        }
        
        // Construir la consulta SQL
        $sql = "INSERT INTO adm_pedidos (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $placeholders) . ")";
        
        file_put_contents($log_file, "SQL a ejecutar (PDO): $sql\n", FILE_APPEND);
        file_put_contents($log_file, "Parámetros: " . print_r($params, true) . "\n", FILE_APPEND);
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
        
        $result = $stmt->execute();
        
        if (!$result) {
            throw new Exception("Error al guardar el pedido: " . implode(", ", $stmt->errorInfo()));
        }
        
        // Preparar la respuesta
        $response = array(
            'status' => 'success',
            'folio' => $folio,
            'message' => 'Pedido guardado correctamente'
        );
        
        // Guardar comentarios SOLO si existen
        if (!empty($comentarios)) {
            $observacion = "APP MÓVIL: " . $comentarios;
            
            // Verificar si existe la tabla obs_pedidos
            $checkTable = $pdo->query("SHOW TABLES LIKE 'obs_pedidos'");
            $tableExists = $checkTable->rowCount() > 0;
            
            if ($tableExists) {
                // Usar PDO para insertar la observación
                $sqlComentario = "INSERT INTO obs_pedidos (folio, observaciones, usuario) 
                               VALUES (:folio, :observacion, :usuario)";
                
                file_put_contents($log_file, "SQL de observación (PDO): $sqlComentario\n", FILE_APPEND);
                
                $stmtComentario = $pdo->prepare($sqlComentario);
                $stmtComentario->bindParam(':folio', $folio);
                $stmtComentario->bindParam(':observacion', $observacion);
                $stmtComentario->bindParam(':usuario', $usuario);
                
                $resultComentario = $stmtComentario->execute();
                
                if (!$resultComentario) {
                    $error_msg = "Error al guardar la observación: " . implode(", ", $stmtComentario->errorInfo());
                    file_put_contents($log_file, "$error_msg\n", FILE_APPEND);
                    $response['warning'] = $error_msg;
                } else {
                    file_put_contents($log_file, "Observación guardada correctamente\n", FILE_APPEND);
                    $response['comment_status'] = 'Observación guardada correctamente: ' . $observacion;
                }
            } else {
                file_put_contents($log_file, "La tabla obs_pedidos no existe, no se guardó la observación\n", FILE_APPEND);
                $response['warning'] = "La tabla obs_pedidos no existe, no se guardó la observación";
            }
        }
    } else {
        // Método alternativo usando MySQLi
        file_put_contents($log_file, "Usando conexión MySQLi alternativa\n", FILE_APPEND);
        
        // Obtener las variables de conexión correctas o usar valores por defecto
        $host_var = isset($host) ? $host : 'localhost';
        $user_var = isset($username) ? $username : 'root';
        $pass_var = isset($password) ? $password : '';
        $db_var = isset($dbname) ? $dbname : 'agrosant_pedidos';
        
        file_put_contents($log_file, "Conectando con: host=$host_var, user=$user_var, db=$db_var\n", FILE_APPEND);
        
        // Conectar usando MySQLi (modernizado)
        $mysqli = new mysqli($host_var, $user_var, $pass_var, $db_var);
        
        if ($mysqli->connect_error) {
            throw new Exception("Error de conexión a la base de datos: " . $mysqli->connect_error);
        }
        
        $mysqli->set_charset('utf8');
        
        // Escapar los valores para prevenir inyección SQL
        $folio_safe = $mysqli->real_escape_string($folio);
        $vendedor_safe = $mysqli->real_escape_string($vendedor);
        $destino_safe = $mysqli->real_escape_string($destino);
        $cliente_safe = $mysqli->real_escape_string($cliente);
        $ruta_safe = $mysqli->real_escape_string($ruta);
        $fecha_salida_safe = $mysqli->real_escape_string($fecha_salida);
        $usuario_safe = $mysqli->real_escape_string($usuario);
        
        // Verificar qué columnas existen en la tabla
        $describeTable = $mysqli->query("DESCRIBE adm_pedidos");
        
        if (!$describeTable) {
            throw new Exception("No se pudo obtener la estructura de la tabla adm_pedidos: " . $mysqli->error);
        }
        
        $columns = [];
        while ($row = $describeTable->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        file_put_contents($log_file, "Columnas encontradas en adm_pedidos: " . implode(", ", $columns) . "\n", FILE_APPEND);
        
        // Construir la consulta SQL basada en las columnas disponibles
        $fields = [];
        $values = [];
        
        if (in_array('folio', $columns)) {
            $fields[] = 'folio';
            $values[] = "'$folio_safe'";
        }
        
        if (in_array('vendedor', $columns)) {
            $fields[] = 'vendedor';
            $values[] = "'$vendedor_safe'";
        }
        
        if (in_array('destino', $columns)) {
            $fields[] = 'destino';
            $values[] = "'$destino_safe'";
        }
        
        if (in_array('cliente', $columns)) {
            $fields[] = 'cliente';
            $values[] = "'$cliente_safe'";
        }
        
        if (in_array('ruta', $columns)) {
            $fields[] = 'ruta';
            $values[] = "'$ruta_safe'";
        }
        
        if (in_array('fecha_salida', $columns)) {
            $fields[] = 'fecha_salida';
            $values[] = "'$fecha_salida_safe'";
        }
        
        if (in_array('status', $columns)) {
            $fields[] = 'status';
            $values[] = "10";
        }
        
        if (in_array('usuario', $columns)) {
            $fields[] = 'usuario';
            $values[] = "'$usuario_safe'";
        }
        
        // Solo agregar 'origen' si la columna existe
        if (in_array('origen', $columns)) {
            $fields[] = 'origen';
            $values[] = "'app_movil'";
        }
        
        // Construir la consulta
        $sql = "INSERT INTO adm_pedidos (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $values) . ")";
        
        file_put_contents($log_file, "SQL a ejecutar (MySQLi): $sql\n", FILE_APPEND);
        $result = $mysqli->query($sql);
        
        if (!$result) {
            throw new Exception("Error al guardar el pedido: " . $mysqli->error);
        }
        
        // Preparar la respuesta
        $response = array(
            'status' => 'success',
            'folio' => $folio,
            'message' => 'Pedido guardado correctamente'
        );
        
        // Guardar comentarios SOLO si existen
        if (!empty($comentarios)) {
            $observacion = "APP MÓVIL: " . $comentarios;
            $observacion_safe = $mysqli->real_escape_string($observacion);
            
            // Verificar si existe la tabla obs_pedidos
            $checkTable = $mysqli->query("SHOW TABLES LIKE 'obs_pedidos'");
            $tableExists = $checkTable && $checkTable->num_rows > 0;
            
            if ($tableExists) {
                // Usar MySQLi para insertar la observación
                $sqlComentario = "INSERT INTO obs_pedidos (folio, observaciones, usuario) 
                               VALUES ('$folio_safe', '$observacion_safe', '$usuario_safe')";
                
                file_put_contents($log_file, "SQL de observación (MySQLi): $sqlComentario\n", FILE_APPEND);
                $resultComentario = $mysqli->query($sqlComentario);
                
                if (!$resultComentario) {
                    $error_msg = "Error al guardar la observación: " . $mysqli->error;
                    file_put_contents($log_file, "$error_msg\n", FILE_APPEND);
                    $response['warning'] = $error_msg;
                } else {
                    file_put_contents($log_file, "Observación guardada correctamente\n", FILE_APPEND);
                    $response['comment_status'] = 'Observación guardada correctamente: ' . $observacion;
                }
            } else {
                file_put_contents($log_file, "La tabla obs_pedidos no existe, no se guardó la observación\n", FILE_APPEND);
                $response['warning'] = "La tabla obs_pedidos no existe, no se guardó la observación";
            }
        }
        
        // Cerrar la conexión MySQLi
        $mysqli->close();
    }
}
catch (Exception $e) {
    // En caso de error, devolver un mensaje claro
    $response = array(
        'status' => 'error',
        'message' => $e->getMessage()
    );
    file_put_contents($log_file, "Error en app_save_order.php: " . $e->getMessage() . "\n", FILE_APPEND);
}

// Devolver la respuesta como JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://127.0.0.1');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Max-Age: 3600');

$json_response = json_encode($response);
file_put_contents($log_file, "Respuesta: $json_response\n", FILE_APPEND);
file_put_contents($log_file, "=== FIN DE PETICIÓN ===\n\n", FILE_APPEND);
echo $json_response;
?>