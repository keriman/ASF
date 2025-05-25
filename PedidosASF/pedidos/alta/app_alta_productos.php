<?php
// Script para recibir productos desde la aplicación móvil
// Similar a app_save_order.php pero para productos

// Crear un archivo de log específico para esta petición
$log_file = __DIR__ . '/app_alta_productos_' . date('Y-m-d') . '.log';
file_put_contents($log_file, "=== NUEVA PETICIÓN: " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
file_put_contents($log_file, "POST: " . print_r($_POST, true) . "\n", FILE_APPEND);

// Activar el registro de errores
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_log("Recibiendo producto desde la app móvil");

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
    $producto = isset($_POST['pp']) ? $_POST['pp'] : '';
    $presentacion = isset($_POST['pr']) ? $_POST['pr'] : '';
    $cantidad = isset($_POST['cc']) ? $_POST['cc'] : '';
    
    // Obtener comentarios y registrarlos
    $comentarios = '';
    if (isset($_POST['cm'])) {
        $comentarios = $_POST['cm'];
        file_put_contents($log_file, "Comentarios encontrados en 'cm': '$comentarios'\n", FILE_APPEND);
    } else {
        file_put_contents($log_file, "No se encontraron comentarios\n", FILE_APPEND);
    }
    
    // Validar datos mínimos requeridos
    if (empty($folio) || empty($producto) || empty($cantidad)) {
        throw new Exception("Faltan datos requeridos (folio, producto, cantidad)");
    }
    
    // Variables para registrar la modificación
    $accion = "";
    $modificado = false;
    
    // Verificar si el producto ya existe
    $resultado = mysql_query("SELECT * FROM prc_pedidos WHERE folio = '$folio' AND producto = '$producto' AND presentacion = '$presentacion' LIMIT 1");
    if (!$resultado) {
        throw new Exception("Error al consultar producto: " . mysql_error());
    }
    
    if (mysql_num_rows($resultado) > 0) {
        // Obtener el registro actual para comparar
        $row = mysql_fetch_array($resultado);
        $cantidad_anterior = $row['cantidad'];
        
        if ($cantidad > 0) {
            $sql = "UPDATE prc_pedidos SET cantidad='$cantidad', procesado=0, usuario='oficina' WHERE folio='$folio' AND producto = '$producto' AND presentacion = '$presentacion'";
            if ($cantidad != $cantidad_anterior) {
                $accion = "Actualizado producto: $producto / $presentacion - Cantidad cambiada de $cantidad_anterior a $cantidad";
                $modificado = true;
            }
        } else {
            $sql = "UPDATE prc_pedidos SET procesado=-1, usuario='oficina' WHERE folio='$folio' AND producto = '$producto' AND presentacion = '$presentacion'";
            $accion = "Eliminado producto: $producto / $presentacion";
            $modificado = true;
        }
    } else {
        // Inserción de nuevo producto
        $sql = "INSERT INTO prc_pedidos (folio, producto, presentacion, cantidad, usuario) VALUES ('$folio', '$producto', '$presentacion', '$cantidad', 'oficina')";
        $accion = "Agregado nuevo producto: $producto / $presentacion - Cantidad: $cantidad";
        $modificado = true;
    }
    
    file_put_contents($log_file, "SQL a ejecutar: $sql\n", FILE_APPEND);
    $result = mysql_query($sql);
    
    if (!$result) {
        throw new Exception("Error al procesar el producto: " . mysql_error());
    }
    
    // Preparar la respuesta
    $response = array(
        'status' => 'success',
        'folio' => $folio,
        'message' => 'Producto procesado correctamente'
    );
    
    // Guardar comentarios si existen - SOLO guardar los comentarios explícitos del usuario
    if (!empty($comentarios)) {
        $observacion_comentario = "(Producto $producto): " . $comentarios;
        $sql_obs_comentario = "INSERT INTO obs_pedidos (folio, observaciones, usuario) VALUES ('$folio', '$observacion_comentario', 'oficina')";
        file_put_contents($log_file, "SQL de observación (comentario): $sql_obs_comentario\n", FILE_APPEND);
        $result_comentario = mysql_query($sql_obs_comentario);
        
        if (!$result_comentario) {
            $error_msg = "Error al guardar el comentario: " . mysql_error();
            file_put_contents($log_file, "$error_msg\n", FILE_APPEND);
            $response['warning'] = $error_msg;
        } else {
            file_put_contents($log_file, "Comentario guardado correctamente\n", FILE_APPEND);
            $response['comment_status'] = 'Comentario guardado correctamente';
        }
    } else {
        file_put_contents($log_file, "No se guardó ningún comentario porque no había comentarios\n", FILE_APPEND);
    }
}
catch (Exception $e) {
    // En caso de error, devolver un mensaje claro
    $response = array(
        'status' => 'error',
        'message' => $e->getMessage()
    );
    file_put_contents($log_file, "Error en app_alta_productos.php: " . $e->getMessage() . "\n", FILE_APPEND);
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