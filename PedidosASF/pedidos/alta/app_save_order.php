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
include '../../conexiones/database.php';

try {
    // Conectar a la base de datos
    $conectar = mysql_connect($host, $user, $clave);
    if (!$conectar) {
        throw new Exception("Error de conexión a la base de datos: " . mysql_error());
    }
    
    mysql_select_db($datbase, $conectar);
    mysql_set_charset('utf8', $conectar);
    
    // Obtener los parámetros
    $folio = isset($_POST['ff']) ? $_POST['ff'] : '';
    $vendedor = isset($_POST['vv']) ? $_POST['vv'] : '';  // Este es el nombre completo del vendedor
    $cliente = isset($_POST['cc']) ? $_POST['cc'] : '';
    $destino = isset($_POST['rr']) ? $_POST['rr'] : '';
    $ruta = isset($_POST['rt']) ? $_POST['rt'] : '';      // Capturar el nuevo campo ruta
    $fecha = isset($_POST['fs']) ? $_POST['fs'] : '';
    
    // Obtener explícitamente el parámetro de usuario (que debe ser OficinaX)
    $usuario = isset($_POST['usuario']) ? $_POST['usuario'] : '';
    
    file_put_contents($log_file, "Parámetros recibidos - Vendedor: '$vendedor', Usuario: '$usuario', Ruta: '$ruta'\n", FILE_APPEND);
    
    // Si el usuario está vacío o no tiene formato Oficina#, usar un valor predeterminado
    if (empty($usuario) || !preg_match('/^Oficina\d+$/i', $usuario)) {
        $usuario = 'Oficina1'; // Valor por defecto
        file_put_contents($log_file, "Usando valor por defecto para usuario: '$usuario'\n", FILE_APPEND);
    }
    
    // Verificar si el usuario tiene formato "Oficina X" (con espacio) y corregirlo
    if (preg_match('/^Oficina\s+(\d+)$/i', $usuario, $matches)) {
        $usuario = 'Oficina' . $matches[1];
        file_put_contents($log_file, "Formato de usuario corregido a: '$usuario'\n", FILE_APPEND);
    }
    
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
    
    // Insertar el pedido incluyendo el campo ruta
    $sql = "INSERT INTO adm_pedidos (folio, vendedor, cliente, destino, ruta, fecha_salida, status, usuario) 
            VALUES ('$folio', '$vendedor', '$cliente', '$destino', '$ruta', '$fecha', 10, '$usuario')";
    
    file_put_contents($log_file, "SQL a ejecutar: $sql\n", FILE_APPEND);
    $result = mysql_query($sql);
    
    if (!$result) {
        throw new Exception("Error al guardar el pedido: " . mysql_error());
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
        
        // Usar el mismo usuario para la observación
        $sqlComentario = "INSERT INTO obs_pedidos (folio, observaciones, usuario) 
                          VALUES ('$folio', '$observacion', '$usuario')";
        
        file_put_contents($log_file, "SQL de observación: $sqlComentario\n", FILE_APPEND);
        $resultComentario = mysql_query($sqlComentario);
        
        if (!$resultComentario) {
            $error_msg = "Error al guardar la observación: " . mysql_error();
            file_put_contents($log_file, "$error_msg\n", FILE_APPEND);
            $response['warning'] = $error_msg;
        } else {
            file_put_contents($log_file, "Observación guardada correctamente\n", FILE_APPEND);
            $response['comment_status'] = 'Observación guardada correctamente: ' . $observacion;
        }
    } else {
        file_put_contents($log_file, "No se guardó ninguna observación porque no había comentarios\n", FILE_APPEND);
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

// Cerrar la conexión
if (isset($conectar)) {
    mysql_close($conectar);
}

// Devolver la respuesta como JSON
header('Content-Type: application/json');
$json_response = json_encode($response);
file_put_contents($log_file, "Respuesta: $json_response\n", FILE_APPEND);
file_put_contents($log_file, "=== FIN DE PETICIÓN ===\n\n", FILE_APPEND);
echo $json_response;
?>