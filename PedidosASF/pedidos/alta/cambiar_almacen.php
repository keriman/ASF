<?php
//pedidos/alta/cambiar_almacen.php
date_default_timezone_set('America/Mexico_City');
include '../../conexiones/database.php';
require_once '../../../InventarioASF/process/InventorySystem2.php';
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'todos';

function mostrarError($mensaje) {
    echo '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Error</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: "error",
                    title: "¡Error!",
                    text: "' . addslashes($mensaje) . '",
                    confirmButtonText: "Aceptar",
                    confirmButtonColor: "#3085d6",
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.history.back();
                    }
                });
            });
        </script>
    </body>
    </html>';
    exit;
}

function mostrarAviso($mensaje, $folio) {
    echo '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Aviso</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: "warning",
                    title: "Verificación de Inventario",
                    html: "' . addslashes($mensaje) . '",
                    confirmButtonText: "Continuar de todas formas",
                    showCancelButton: true,
                    cancelButtonText: "Cancelar",
                    confirmButtonColor: "#ff9800",
                    cancelButtonColor: "#3085d6",
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = "procesar_almacen.php?gg=' . $folio . '&ignorar_stock=1";
                    } else {
                        window.history.back();
                    }
                });
            });
        </script>
    </body>
    </html>';
    exit;
}

function mostrarExito($mensaje, $productos_disponibles, $productos_faltantes) {
    $detalleStock = '';
    if (!empty($productos_faltantes)) {
        $detalleStock .= "<p><strong>Productos con stock insuficiente:</strong></p><ul>";
        foreach ($productos_faltantes as $prod) {
            $detalleStock .= "<li>" . $prod['name'] . " - Disponible: " . $prod['stock_actual'] . ", Requerido: " . $prod['quantity'] . "</li>";
        }
        $detalleStock .= "</ul>";
    }
    
    if (!empty($productos_disponibles)) {
        $detalleStock .= "<p><strong>Productos disponibles:</strong></p><ul>";
        foreach ($productos_disponibles as $prod) {
            $detalleStock .= "<li>" . $prod['name'] . " - Disponible: " . $prod['stock_actual'] . ", Requerido: " . $prod['quantity'] . "</li>";
        }
        $detalleStock .= "</ul>";
    }

    echo '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Éxito</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: "success",
                    title: "¡Pedido Enviado al Almacén!",
                    html: "' . addslashes($mensaje) . '<br><br>' . addslashes($detalleStock) . '",
                    confirmButtonText: "Aceptar",
                    confirmButtonColor: "#4CAF50",
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = "../index.php?tab=' . $tab . '";
                    }
                });
            });
        </script>
    </body>
    </html>';
    exit;
}

function normalizeProductName($name) {
    // Asegurarse de que haya exactamente un espacio antes del paréntesis
    $name = preg_replace('/\s*\(/', ' (', $name);
    // Eliminar espacios extra al inicio y final
    $name = trim($name);
    return $name;
}

session_start();
if (!isset($_SESSION['username']) && !isset($_SESSION['userid'])) {
    mostrarError("Sesión no iniciada");
}
if ($_SESSION["tipo"] != "oficina") {
    mostrarError("Usuario no autorizado");
}

try {
    $conectar = mysql_connect($host, $user, $clave);
    if (!$conectar) {
        throw new Exception("Error conectando a MySQL: " . mysql_error());
    }
    
    if (!mysql_select_db($datbase, $conectar)) {
        throw new Exception("Error seleccionando base de datos: " . mysql_error());
    }
    
    mysql_set_charset('utf8', $conectar);
    
    if (empty($_GET['gg'])) {
        throw new Exception("No se recibió folio");
    }
    
    $subs_folio = htmlentities($_GET['gg']);
    $usuario = $_SESSION["username"];
    $subs_usuario = $usuario;
    
    // Initialize InventorySystem
    $inventorySystem = new InventorySystem();
    
    // Verificamos el folio
    $buscar = "SELECT folio, status FROM adm_pedidos WHERE folio = " . $subs_folio . " LIMIT 1";
    $resultado = mysql_query($buscar);
    
    if (!$resultado || mysql_num_rows($resultado) == 0) {
        throw new Exception("Folio no encontrado: $subs_folio");
    }
    
    $row = mysql_fetch_array($resultado);
    $folio = $row['folio'];
    $status = $row['status'];
    
    if ($status != 10) {
        throw new Exception("El pedido no está en estado Pendiente. Estado actual: $status");
    }
    
    // Get all products from the order
    $query_productos = "SELECT producto, presentacion, cantidad FROM prc_pedidos 
                      WHERE folio = '$folio' AND procesado >= 0";
    $result_productos = mysql_query($query_productos);
    
    if (!$result_productos) {
        throw new Exception("Error obteniendo productos: " . mysql_error());
    }
    
    // Arrays para almacenar la información de los productos
    $productos_disponibles = array();
    $productos_faltantes = array();
    $todos_disponibles = true;
    
    // Verificar disponibilidad en inventario para cada producto
    while ($producto = mysql_fetch_array($result_productos)) {
        $product_name_original = $producto['producto'];
        $presentacion = $producto['presentacion'];
        $quantity = intval($producto['cantidad']);
        
        // Normalizar el nombre del producto
        $normalized_name = normalizeProductName($product_name_original);
        
        // Search for product in inventory
        $products = $inventorySystem->listProducts();
        $productId = null;
        $stock_actual = 0;
        
        foreach ($products as $invProduct) {
            if (strcasecmp($normalized_name, $invProduct['name']) === 0) {
                $productId = $invProduct['id'];
                $stock_actual = $invProduct['stock'];
                break;
            }
        }
        
        // Si el producto no existe en inventario o no hay suficiente stock
        if (!$productId) {
            $productos_faltantes[] = array(
                'name' => $normalized_name,
                'quantity' => $quantity,
                'stock_actual' => 'No encontrado',
                'presentacion' => $presentacion
            );
            $todos_disponibles = false;
        } elseif ($stock_actual < $quantity) {
            $productos_faltantes[] = array(
                'id' => $productId,
                'name' => $normalized_name,
                'quantity' => $quantity,
                'stock_actual' => $stock_actual,
                'presentacion' => $presentacion
            );
            $todos_disponibles = false;
        } else {
            $productos_disponibles[] = array(
                'id' => $productId,
                'name' => $normalized_name,
                'quantity' => $quantity,
                'stock_actual' => $stock_actual,
                'presentacion' => $presentacion
            );
        }
    }
    
    // Si todos los productos están disponibles
    if ($todos_disponibles) {
        // Begin MySQL transaction
        if (!mysql_query("START TRANSACTION")) {
            throw new Exception("Error iniciando transacción MySQL: " . mysql_error());
        }
        
        // Update order status
        $sql = "UPDATE adm_pedidos SET status=30 WHERE folio='$folio'";
        if (!mysql_query($sql)) {
            throw new Exception("Error actualizando status: " . mysql_error());
        }
        
        // Add comment
        $comenta = "Enviado al Almacen";
        $sql = "INSERT INTO obs_pedidos (folio, observaciones, usuario) 
               VALUES ('$folio', '$comenta', '$subs_usuario')";
        if (!mysql_query($sql)) {
            throw new Exception("Error insertando comentario: " . mysql_error());
        }
        
        // Commit MySQL transaction
        if (!mysql_query("COMMIT")) {
            throw new Exception("Error en COMMIT MySQL: " . mysql_error());
        }
        
        // Mostrar mensaje de éxito
        mostrarExito("Todos los productos están disponibles en inventario.", $productos_disponibles, array());
    } else {
        // Hay productos con stock insuficiente
        $mensaje = "Hay productos con stock insuficiente en el inventario.<br>";
        $mensaje .= "¿Desea continuar enviando el pedido al almacén de todas formas?";
        
        mostrarAviso($mensaje, $folio);
    }
    
} catch (Exception $e) {
    // Asegurar que se haga rollback en caso de error
    mysql_query("ROLLBACK");
    mostrarError($e->getMessage());
}

mysql_close($conectar);
?>