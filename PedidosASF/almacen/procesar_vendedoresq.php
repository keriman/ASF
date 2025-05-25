<?php
// /PedidosASF/almacen/procesar_vendedores.php
// Este archivo solo procesa solicitudes AJAX para vendedores

// Depuración - registrar solicitud
error_log('Procesando solicitud AJAX vendedores: ' . print_r($_POST, true));

// Incluir el archivo de conexión a la base de datos
include($_SERVER['DOCUMENT_ROOT'] . '/PedidosASF/conexiones/database.php');

// Conectar a la base de datos
$conectar = mysql_connect($host, $user, $clave);
mysql_select_db($datbase, $conectar);
mysql_set_charset('utf8', $conectar);

// Procesar la acción solicitada
$action = isset($_POST['action']) ? $_POST['action'] : '';
$response = array();

try {
    switch ($action) {
        case 'update':
            // Actualizar un registro existente
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $campo = isset($_POST['campo']) ? $_POST['campo'] : '';
            $valor = isset($_POST['valor']) ? trim($_POST['valor']) : '';
            
            // Validaciones
            if ($id <= 0) {
                $response = array('status' => 'error', 'message' => 'ID inválido');
            } elseif (!in_array($campo, array('nombre', 'telefono'))) {
                $response = array('status' => 'error', 'message' => 'Campo no permitido');
            } else {
                // Para el campo nombre, convertir a mayúsculas
                if ($campo == 'nombre') {
                    $valor = strtoupper($valor);
                    
                    // Verificar si ya existe un vendedor con ese nombre
                    $verificar_nombre = "SELECT id FROM vendedores_quinagro WHERE nombre = '" . mysql_real_escape_string($valor) . "' AND id != $id LIMIT 1";
                    $resultado_nombre = mysql_query($verificar_nombre);
                    
                    if (mysql_num_rows($resultado_nombre) > 0) {
                        $response = array('status' => 'error', 'message' => 'Ya existe un vendedor con ese nombre');
                        break;
                    }
                }
                
                // Actualizar en la base de datos
                $sql = "UPDATE vendedores_quinagro SET " . $campo . " = '" . mysql_real_escape_string($valor) . "' WHERE id = $id";
                
                if (mysql_query($sql)) {
                    $response = array('status' => 'success', 'message' => 'Datos actualizados correctamente');
                } else {
                    $response = array('status' => 'error', 'message' => 'Error al actualizar: ' . mysql_error());
                }
            }
            break;
            
        case 'add':
            // Agregar un nuevo vendedor
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $nombre = isset($_POST['nombre']) ? strtoupper(trim($_POST['nombre'])) : '';
            $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
            
            // Validaciones
            if ($id <= 0) {
                $response = array('status' => 'error', 'message' => 'ID inválido');
            } elseif (empty($nombre)) {
                $response = array('status' => 'error', 'message' => 'El nombre es obligatorio');
            } else {
                // Verificar si ya existe un ID igual
                $verificar_id = "SELECT id FROM vendedores_quinagro WHERE id = $id LIMIT 1";
                $resultado_id = mysql_query($verificar_id);
                
                if (mysql_num_rows($resultado_id) > 0) {
                    $response = array('status' => 'error', 'message' => 'Ya existe un vendedor con ese ID');
                } else {
                    // Verificar si ya existe un nombre igual
                    $verificar_nombre = "SELECT id FROM vendedores_quinagro WHERE nombre = '" . mysql_real_escape_string($nombre) . "' LIMIT 1";
                    $resultado_nombre = mysql_query($verificar_nombre);
                    
                    if (mysql_num_rows($resultado_nombre) > 0) {
                        $response = array('status' => 'error', 'message' => 'Ya existe un vendedor con ese nombre');
                    } else {
                        // Insertar el nuevo vendedor
                        $sql = "INSERT INTO vendedores_quinagro (id, nombre, telefono) 
                                VALUES ($id, '" . mysql_real_escape_string($nombre) . "', '" . mysql_real_escape_string($telefono) . "')";
                        
                        if (mysql_query($sql)) {
                            $response = array(
                                'status' => 'success',
                                'message' => 'Vendedor agregado correctamente',
                                'id' => $id,
                                'nombre' => $nombre,
                                'telefono' => $telefono
                            );
                        } else {
                            $response = array('status' => 'error', 'message' => 'Error al agregar: ' . mysql_error());
                        }
                    }
                }
            }
            break;
            
        case 'delete':
            // Eliminar un vendedor
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            
            // Validaciones
            if ($id <= 0) {
                $response = array('status' => 'error', 'message' => 'ID inválido');
            } else {
                // Verificar si el vendedor tiene pedidos asociados
                $verificar_pedidos = "SELECT COUNT(*) as total FROM adm_pedidos WHERE vendedor IN (SELECT nombre FROM vendedores_quinagro WHERE id = $id)";
                $resultado_pedidos = mysql_query($verificar_pedidos);
                $row_pedidos = mysql_fetch_assoc($resultado_pedidos);
                
                if ($row_pedidos['total'] > 0) {
                    $response = array('status' => 'error', 'message' => 'No se puede eliminar el vendedor porque tiene pedidos asociados');
                } else {
                    // Eliminar el vendedor
                    $sql = "DELETE FROM vendedores_quinagro WHERE id = $id";
                    
                    if (mysql_query($sql)) {
                        $response = array('status' => 'success', 'message' => 'Vendedor eliminado correctamente');
                    } else {
                        $response = array('status' => 'error', 'message' => 'Error al eliminar: ' . mysql_error());
                    }
                }
            }
            break;
            
        default:
            $response = array('status' => 'error', 'message' => 'Acción no válida');
            break;
    }
} catch (Exception $e) {
    error_log('Error en procesar_vendedores.php: ' . $e->getMessage());
    $response = array('status' => 'error', 'message' => 'Error interno: ' . $e->getMessage());
}

// Limpiar cualquier salida anterior para evitar problemas JSON
if (ob_get_length()) ob_clean();

// Devolver respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>