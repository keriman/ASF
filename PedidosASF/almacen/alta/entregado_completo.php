<?php
//almacen/alta/entregado_completo.php
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
if ($_SESSION["tipo"] != "almacen") {
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
    
    if ($status == 30) {
        // Begin MySQL transaction for pedidos database
        if (!mysql_query("START TRANSACTION")) {
            throw new Exception("Error iniciando transacción MySQL: " . mysql_error());
        }
        
        // Get all products from the order
        $query_productos = "SELECT producto, presentacion, cantidad FROM prc_pedidos 
                          WHERE folio = '$folio' AND procesado >= 0";
        $result_productos = mysql_query($query_productos);
        
        if (!$result_productos) {
            throw new Exception("Error obteniendo productos: " . mysql_error());
        }
        
        // Lista de productos para validación
        $productos_a_procesar = array();
        $cantidad_total_productos = 0;
        
        // PASO 1: Verificar que todos los productos existan en inventario y tengan stock suficiente
        while ($producto = mysql_fetch_array($result_productos)) {
            $product_name_original = $producto['producto'];
            $presentacion = $producto['presentacion'];
            $quantity = intval($producto['cantidad']);
            $cantidad_total_productos++;
            
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
            
            if (!$productId) {
                throw new Exception("Producto no encontrado en inventario: $normalized_name");
            }
            
            // Verificar si hay suficiente stock
            if ($stock_actual < $quantity) {
                throw new Exception("Stock insuficiente para $normalized_name. Disponible: $stock_actual, Requerido: $quantity");
            }
            
            // Producto validado, agregarlo a la lista para procesar después
            $productos_a_procesar[] = array(
                'id' => $productId,
                'name' => $normalized_name,
                'quantity' => $quantity,
                'presentacion' => $presentacion
            );
        }
        
        // PASO 2: Procesar todos los productos (solo si todos pasaron la validación)
        $productos_procesados = 0;
        
        foreach ($productos_a_procesar as $producto) {
            $productId = $producto['id'];
            $normalized_name = $producto['name'];
            $quantity = $producto['quantity'];
            $presentacion = $producto['presentacion'];
            
            // Update inventory stock
            $notes = "Entrega completa - Folio: $folio | Presentación: $presentacion";
            if (!$inventorySystem->updateStock($productId, $quantity, 'remove', $notes)) {
                throw new Exception("Error actualizando stock para producto: $normalized_name");
            }
            
            $productos_procesados++;
        }
        
        // Verificar que todos los productos se procesaron correctamente
        if ($productos_procesados != $cantidad_total_productos) {
            throw new Exception("No se procesaron todos los productos. Procesados: $productos_procesados, Total: $cantidad_total_productos");
        }
        
        // Update order status
        $sql = "UPDATE adm_pedidos SET status=40 WHERE folio='$folio'";
        if (!mysql_query($sql)) {
            throw new Exception("Error actualizando status: " . mysql_error());
        }
        
        // Add comment
        $comenta = "Entregado completo";
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
                        title: "¡Entrega Exitosa!",
                        text: "Se procesaron ' . $productos_procesados . ' productos correctamente",
                        confirmButtonText: "Aceptar",
                        confirmButtonColor: "#4CAF50",
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = "../index.php?gg=' . $folio . '";
                        }
                    });
                });
            </script>
        </body>
        </html>';
        
    } else {
        throw new Exception("Status incorrecto para procesar: $status");
    }
    
} catch (Exception $e) {
    // Asegurar que se haga rollback en caso de error
    mysql_query("ROLLBACK");
    mostrarError($e->getMessage());
}

mysql_close($conectar);
?>