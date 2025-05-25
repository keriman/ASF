<?php
//pedidos/alta/procesar_almacen.php
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
    $ignorar_stock = isset($_GET['ignorar_stock']) && $_GET['ignorar_stock'] == 1;
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
    
    // Begin MySQL transaction
    if (!mysql_query("START TRANSACTION")) {
        throw new Exception("Error iniciando transacción MySQL: " . mysql_error());
    }
    
    // Verificar disponibilidad en inventario solo si no se está ignorando el stock
    $productos_disponibles = array();
    $productos_faltantes = array();
    $mensaje_observacion = "";
    
    if (!$ignorar_stock) {
        throw new Exception("Este script debe ser llamado con ignorar_stock=1");
    }
    
    // Get all products from the order
    $query_productos = "SELECT producto, presentacion, cantidad FROM prc_pedidos 
                      WHERE folio = '$folio' AND procesado >= 0";
    $result_productos = mysql_query($query_productos);
    
    if (!$result_productos) {
        throw new Exception("Error obteniendo productos: " . mysql_error());
    }
    
    // Verificar disponibilidad y construir mensaje de observación
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
            $mensaje_observacion .= "PENDIENTE: " . $normalized_name . " (" . $presentacion . ") - No existe en inventario, Requerido: " . $quantity . ". ";
        } elseif ($stock_actual < $quantity) {
            $productos_faltantes[] = array(
                'id' => $productId,
                'name' => $normalized_name,
                'quantity' => $quantity,
                'stock_actual' => $stock_actual,
                'presentacion' => $presentacion
            );
            $mensaje_observacion .= "PENDIENTE: " . $normalized_name . " (" . $presentacion . ") - Disponible: " . $stock_actual . ", Requerido: " . $quantity . ". ";
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
    
    // Update order status
    $sql = "UPDATE adm_pedidos SET status=30 WHERE folio='$folio'";
    if (!mysql_query($sql)) {
        throw new Exception("Error actualizando status: " . mysql_error());
    }
    
    // Add standard comment
    $comenta = "Enviado al Almacen";
    $sql = "INSERT INTO obs_pedidos (folio, observaciones, usuario) 
           VALUES ('$folio', '$comenta', '$subs_usuario')";
    if (!mysql_query($sql)) {
        throw new Exception("Error insertando comentario: " . mysql_error());
    }
    
    // Add observation about missing products if any
    if (!empty($productos_faltantes)) {
        $sql = "INSERT INTO obs_pedidos (folio, observaciones, usuario, modificada) 
               VALUES ('$folio', '$mensaje_observacion', '$subs_usuario', 1)";
        if (!mysql_query($sql)) {
            throw new Exception("Error insertando comentario sobre productos faltantes: " . mysql_error());
        }
    }
    
    // Commit MySQL transaction
    if (!mysql_query("COMMIT")) {
        throw new Exception("Error en COMMIT MySQL: " . mysql_error());
    }
    
    // Mostrar mensaje de éxito
    if (!empty($productos_faltantes)) {
        mostrarExito("El pedido ha sido enviado al almacén con observaciones de productos faltantes.", $productos_disponibles, $productos_faltantes);
    } else {
        mostrarExito("El pedido ha sido enviado al almacén correctamente.", $productos_disponibles, array());
    }
    
} catch (Exception $e) {
    // Asegurar que se haga rollback en caso de error
    mysql_query("ROLLBACK");
    mostrarError($e->getMessage());
}

mysql_close($conectar);
?>