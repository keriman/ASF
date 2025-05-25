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
include '../../pages/conexion.php';

// Registrar las variables de conexión disponibles
file_put_contents($log_file, "Variables de conexión disponibles:\n", FILE_APPEND);
file_put_contents($log_file, "- host: " . (isset($host) ? $host : 'no disponible') . "\n", FILE_APPEND);
file_put_contents($log_file, "- username: " . (isset($username) ? 'disponible' : 'no disponible') . "\n", FILE_APPEND);
file_put_contents($log_file, "- dbname: " . (isset($dbname) ? $dbname : 'no disponible') . "\n", FILE_APPEND);
file_put_contents($log_file, "- pdo: " . (isset($pdo) ? 'disponible' : 'no disponible') . "\n", FILE_APPEND);

try {
    // Obtener los parámetros
    $folio = isset($_POST['ff']) ? trim($_POST['ff']) : '';
    $producto = isset($_POST['pp']) ? trim($_POST['pp']) : '';
    $presentacion = isset($_POST['pr']) ? trim($_POST['pr']) : '';
    $cantidad = isset($_POST['cc']) ? trim($_POST['cc']) : '';
    
    // Obtener comentarios y registrarlos
    $comentarios = '';
    if (isset($_POST['cm'])) {
        $comentarios = trim($_POST['cm']);
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
    
    // Verificar qué tipo de conexión usar (PDO o MySQLi)
    if (isset($pdo) && $pdo instanceof PDO) {
        file_put_contents($log_file, "Usando conexión PDO\n", FILE_APPEND);
        
        // Verificar si el producto ya existe usando PDO
        $stmt = $pdo->prepare("SELECT * FROM prc_pedidos WHERE folio = :folio AND producto = :producto AND presentacion = :presentacion LIMIT 1");
        $stmt->bindParam(':folio', $folio);
        $stmt->bindParam(':producto', $producto);
        $stmt->bindParam(':presentacion', $presentacion);
        $stmt->execute();
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Producto existe, actualizar o eliminar según la cantidad
            $cantidad_anterior = $row['cantidad'];
            
            if ($cantidad > 0) {
                // Actualizar el producto
                $sql = "UPDATE prc_pedidos SET cantidad = :cantidad, procesado = 0, usuario = 'Oficina4' 
                        WHERE folio = :folio AND producto = :producto AND presentacion = :presentacion";
                
                $update_stmt = $pdo->prepare($sql);
                $update_stmt->bindParam(':cantidad', $cantidad);
                $update_stmt->bindParam(':folio', $folio);
                $update_stmt->bindParam(':producto', $producto);
                $update_stmt->bindParam(':presentacion', $presentacion);
                
                file_put_contents($log_file, "SQL a ejecutar (UPDATE): $sql\n", FILE_APPEND);
                $result = $update_stmt->execute();
                
                if ($cantidad != $cantidad_anterior) {
                    $accion = "Actualizado producto: $producto / $presentacion - Cantidad cambiada de $cantidad_anterior a $cantidad";
                    $modificado = true;
                }
            } else {
                // Eliminar el producto (marcar como eliminado)
                $sql = "UPDATE prc_pedidos SET procesado = -1, usuario = 'oficina4' 
                        WHERE folio = :folio AND producto = :producto AND presentacion = :presentacion";
                
                $delete_stmt = $pdo->prepare($sql);
                $delete_stmt->bindParam(':folio', $folio);
                $delete_stmt->bindParam(':producto', $producto);
                $delete_stmt->bindParam(':presentacion', $presentacion);
                
                file_put_contents($log_file, "SQL a ejecutar (DELETE): $sql\n", FILE_APPEND);
                $result = $delete_stmt->execute();
                
                $accion = "Eliminado producto: $producto / $presentacion";
                $modificado = true;
            }
        } else {
            // Insertar nuevo producto
            $sql = "INSERT INTO prc_pedidos (folio, producto, presentacion, cantidad, usuario) 
                    VALUES (:folio, :producto, :presentacion, :cantidad, 'oficina4')";
            
            $insert_stmt = $pdo->prepare($sql);
            $insert_stmt->bindParam(':folio', $folio);
            $insert_stmt->bindParam(':producto', $producto);
            $insert_stmt->bindParam(':presentacion', $presentacion);
            $insert_stmt->bindParam(':cantidad', $cantidad);
            
            file_put_contents($log_file, "SQL a ejecutar (INSERT): $sql\n", FILE_APPEND);
            $result = $insert_stmt->execute();
            
            $accion = "Agregado nuevo producto: $producto / $presentacion - Cantidad: $cantidad";
            $modificado = true;
        }
        
        if ($result === false) {
            throw new Exception("Error al procesar el producto: " . implode(", ", $pdo->errorInfo()));
        }
        
        // Guardar la acción como observación si hubo modificación
        if ($modificado && !empty($accion)) {
            $observacion = "[APP] $accion";
            
            // Verificar si la tabla obs_pedidos existe
            $check_table = $pdo->query("SHOW TABLES LIKE 'obs_pedidos'");
            if ($check_table && $check_table->rowCount() > 0) {
                $sql_obs = "INSERT INTO obs_pedidos (folio, observaciones, usuario) VALUES (:folio, :observaciones, 'oficina4')";
                $obs_stmt = $pdo->prepare($sql_obs);
                $obs_stmt->bindParam(':folio', $folio);
                $obs_stmt->bindParam(':observaciones', $observacion);
                
                file_put_contents($log_file, "SQL de observación (acción): $sql_obs\n", FILE_APPEND);
                $result_obs = $obs_stmt->execute();
                
                if (!$result_obs) {
                    file_put_contents($log_file, "Error al guardar la observación: " . implode(", ", $obs_stmt->errorInfo()) . "\n", FILE_APPEND);
                } else {
                    file_put_contents($log_file, "Observación guardada correctamente\n", FILE_APPEND);
                }
            } else {
                file_put_contents($log_file, "Tabla obs_pedidos no encontrada, no se guardó la observación de acción\n", FILE_APPEND);
            }
        }
        
        // Preparar la respuesta
        $response = array(
            'status' => 'success',
            'folio' => $folio,
            'message' => 'Producto procesado correctamente',
            'accion' => $accion
        );
        
        // Guardar comentarios SOLO si existen
        if (!empty($comentarios)) {
            $observacion_comentario = "(Producto $producto): " . $comentarios;
            
            // Verificar si la tabla obs_pedidos existe
            $check_table = $pdo->query("SHOW TABLES LIKE 'obs_pedidos'");
            if ($check_table && $check_table->rowCount() > 0) {
                $sql_obs_comentario = "INSERT INTO obs_pedidos (folio, observaciones, usuario) VALUES (:folio, :observaciones, 'oficina4')";
                $obs_comentario_stmt = $pdo->prepare($sql_obs_comentario);
                $obs_comentario_stmt->bindParam(':folio', $folio);
                $obs_comentario_stmt->bindParam(':observaciones', $observacion_comentario);
                
                file_put_contents($log_file, "SQL de observación (comentario): $sql_obs_comentario\n", FILE_APPEND);
                $result_comentario = $obs_comentario_stmt->execute();
                
                if (!$result_comentario) {
                    $error_msg = "Error al guardar el comentario: " . implode(", ", $obs_comentario_stmt->errorInfo());
                    file_put_contents($log_file, "$error_msg\n", FILE_APPEND);
                    $response['warning'] = $error_msg;
                } else {
                    file_put_contents($log_file, "Comentario guardado correctamente\n", FILE_APPEND);
                    $response['comment_status'] = 'Comentario guardado correctamente';
                }
            } else {
                file_put_contents($log_file, "Tabla obs_pedidos no encontrada, no se guardó el comentario\n", FILE_APPEND);
                $response['warning'] = "No se pudo guardar el comentario (tabla obs_pedidos no encontrada)";
            }
        } else {
            file_put_contents($log_file, "No se guardó ningún comentario porque no había comentarios\n", FILE_APPEND);
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
        
        // Conectar usando MySQLi
        $mysqli = new mysqli($host_var, $user_var, $pass_var, $db_var);
        
        if ($mysqli->connect_error) {
            throw new Exception("Error de conexión a la base de datos: " . $mysqli->connect_error);
        }
        
        $mysqli->set_charset('utf8');
        
        // Escapar para prevenir inyección SQL
        $folio_safe = $mysqli->real_escape_string($folio);
        $producto_safe = $mysqli->real_escape_string($producto);
        $presentacion_safe = $mysqli->real_escape_string($presentacion);
        $cantidad_safe = $mysqli->real_escape_string($cantidad);
        
        // Verificar si el producto ya existe
        $sql_check = "SELECT * FROM prc_pedidos WHERE folio = '$folio_safe' AND producto = '$producto_safe' AND presentacion = '$presentacion_safe' LIMIT 1";
        $result_check = $mysqli->query($sql_check);
        
        if (!$result_check) {
            throw new Exception("Error al consultar producto: " . $mysqli->error);
        }
        
        if ($result_check->num_rows > 0) {
            // Obtener el registro actual para comparar
            $row = $result_check->fetch_assoc();
            $cantidad_anterior = $row['cantidad'];
            
            if ($cantidad > 0) {
                $sql = "UPDATE prc_pedidos SET cantidad='$cantidad_safe', procesado=0, usuario='oficina4' 
                        WHERE folio='$folio_safe' AND producto = '$producto_safe' AND presentacion = '$presentacion_safe'";
                
                if ($cantidad != $cantidad_anterior) {
                    $accion = "Actualizado producto: $producto / $presentacion - Cantidad cambiada de $cantidad_anterior a $cantidad";
                    $modificado = true;
                }
            } else {
                $sql = "UPDATE prc_pedidos SET procesado=-1, usuario='oficina4' 
                        WHERE folio='$folio_safe' AND producto = '$producto_safe' AND presentacion = '$presentacion_safe'";
                
                $accion = "Eliminado producto: $producto / $presentacion";
                $modificado = true;
            }
        } else {
            // Inserción de nuevo producto
            $sql = "INSERT INTO prc_pedidos (folio, producto, presentacion, cantidad, usuario) 
                    VALUES ('$folio_safe', '$producto_safe', '$presentacion_safe', '$cantidad_safe', 'oficina4')";
            
            $accion = "Agregado nuevo producto: $producto / $presentacion - Cantidad: $cantidad";
            $modificado = true;
        }
        
        file_put_contents($log_file, "SQL a ejecutar: $sql\n", FILE_APPEND);
        $result = $mysqli->query($sql);
        
        if (!$result) {
            throw new Exception("Error al procesar el producto: " . $mysqli->error);
        }
        
        // Guardar la acción como observación si hubo modificación
        if ($modificado && !empty($accion)) {
            $observacion = "[APP] $accion";
            $observacion_safe = $mysqli->real_escape_string($observacion);
            
            // Verificar si la tabla obs_pedidos existe
            $check_table = $mysqli->query("SHOW TABLES LIKE 'obs_pedidos'");
            if ($check_table && $check_table->num_rows > 0) {
                $sql_obs = "INSERT INTO obs_pedidos (folio, observaciones, usuario) VALUES ('$folio_safe', '$observacion_safe', 'oficina4')";
                file_put_contents($log_file, "SQL de observación (acción): $sql_obs\n", FILE_APPEND);
                $result_obs = $mysqli->query($sql_obs);
                
                if (!$result_obs) {
                    file_put_contents($log_file, "Error al guardar la observación: " . $mysqli->error . "\n", FILE_APPEND);
                } else {
                    file_put_contents($log_file, "Observación guardada correctamente\n", FILE_APPEND);
                }
            } else {
                file_put_contents($log_file, "Tabla obs_pedidos no encontrada, no se guardó la observación de acción\n", FILE_APPEND);
            }
        }
        
        // Preparar la respuesta
        $response = array(
            'status' => 'success',
            'folio' => $folio,
            'message' => 'Producto procesado correctamente',
            'accion' => $accion
        );
        
        // Guardar comentarios si existen
        if (!empty($comentarios)) {
            $observacion_comentario = "(Producto $producto): " . $comentarios;
            $observacion_comentario_safe = $mysqli->real_escape_string($observacion_comentario);
            
            // Verificar si la tabla obs_pedidos existe
            $check_table = $mysqli->query("SHOW TABLES LIKE 'obs_pedidos'");
            if ($check_table && $check_table->num_rows > 0) {
                $sql_obs_comentario = "INSERT INTO obs_pedidos (folio, observaciones, usuario) VALUES ('$folio_safe', '$observacion_comentario_safe', 'oficina4')";
                file_put_contents($log_file, "SQL de observación (comentario): $sql_obs_comentario\n", FILE_APPEND);
                $result_comentario = $mysqli->query($sql_obs_comentario);
                
                if (!$result_comentario) {
                    $error_msg = "Error al guardar el comentario: " . $mysqli->error;
                    file_put_contents($log_file, "$error_msg\n", FILE_APPEND);
                    $response['warning'] = $error_msg;
                } else {
                    file_put_contents($log_file, "Comentario guardado correctamente\n", FILE_APPEND);
                    $response['comment_status'] = 'Comentario guardado correctamente';
                }
            } else {
                file_put_contents($log_file, "Tabla obs_pedidos no encontrada, no se guardó el comentario\n", FILE_APPEND);
                $response['warning'] = "No se pudo guardar el comentario (tabla obs_pedidos no encontrada)";
            }
        } else {
            file_put_contents($log_file, "No se guardó ningún comentario porque no había comentarios\n", FILE_APPEND);
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
    file_put_contents($log_file, "Error en app_alta_productos.php: " . $e->getMessage() . "\n", FILE_APPEND);
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