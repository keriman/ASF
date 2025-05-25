<?php
// exportar_excel.php - Este archivo debe guardarse en la misma carpeta que index.php (pedidos/)

// Iniciar sesión y verificar acceso
session_start();
if (!isset($_SESSION['username']) && !isset($_SESSION['userid'])) {
    header('Location: ../login.php');
    exit;
}
if ($_SESSION["tipo"] != "oficina") {
    header('Location: ../index.php');
    exit;
}

// Incluir conexión a la base de datos
include '../conexiones/database.php';

// Establecer conexión
$conectar = mysql_connect($host, $user, $clave);
mysql_select_db($datbase, $conectar);
mysql_set_charset('utf8', $conectar);

// Determinar modo de exportación (GET o POST)
$exportar_todos = false;
$pedidos_seleccionados = array();
$vendedor = "";
$titulo = "";
$condicion = "";
$filename = "";

// Procesar parámetros según el método
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Exportación de pedidos seleccionados
    if (!isset($_POST['vendedor']) || empty($_POST['vendedor'])) {
        die("Error: Vendedor no especificado");
    }
    
    if (!isset($_POST['pedidos']) || !is_array($_POST['pedidos']) || count($_POST['pedidos']) == 0) {
        die("Error: No se seleccionaron pedidos para exportar");
    }
    
    $vendedor = $_POST['vendedor'];
    $pedidos_seleccionados = array_map('intval', $_POST['pedidos']); // Convertir a enteros
    
    $titulo = "Pedidos seleccionados de " . $vendedor;
    $condicion = "WHERE vendedor = '" . mysql_real_escape_string($vendedor) . "' 
                 AND folio IN (" . implode(',', $pedidos_seleccionados) . ")";
    $filename = "Pedidos_Seleccionados_" . str_replace(' ', '_', $vendedor) . "_" . date('Y-m-d');
} else {
    // Exportación por URL (GET) - un vendedor o todos
    if (!isset($_GET['vendedor']) || empty($_GET['vendedor'])) {
        die("Error: Vendedor no especificado");
    }
    
    $vendedor = urldecode($_GET['vendedor']);
    
    if ($vendedor == "TODOS") {
        $exportar_todos = true;
        $titulo = "Todos los Pedidos";
        $condicion = ""; // Sin condición WHERE específica para vendedor
        $filename = "Todos_Los_Pedidos_" . date('Y-m-d');
    } else {
        $titulo = "Pedidos de " . $vendedor;
        $condicion = "WHERE vendedor = '" . mysql_real_escape_string($vendedor) . "'";
        $filename = "Pedidos_" . str_replace(' ', '_', $vendedor) . "_" . date('Y-m-d');
    }
}

// Establecer encabezados para descarga de Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
header('Cache-Control: max-age=0');

// Obtener los pedidos según el criterio
$sql_pedidos = "SELECT 
                    adm_pedidos.folio, 
                    adm_pedidos.vendedor, 
                    adm_pedidos.destino, 
                    adm_pedidos.cliente, 
                    adm_pedidos.ruta, 
                    adm_pedidos.fecha_salida, 
                    adm_pedidos.status,
                    adm_pedidos.FR as fecha_registro
                FROM 
                    adm_pedidos 
                " . $condicion . "
                ORDER BY 
                    vendedor, fecha_salida DESC, folio DESC";

$result_pedidos = mysql_query($sql_pedidos) or die("Error al obtener pedidos: " . mysql_error());

// Comenzar a crear el archivo Excel como HTML (Excel puede abrir HTML formateado como tabla)
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($titulo); ?></title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1><?php echo htmlspecialchars($titulo); ?></h1>
    <p>Generado el <?php echo date('d/m/Y H:i:s'); ?></p>
    
    <table>
        <thead>
            <tr>
                <th>Folio</th>
                <?php if ($exportar_todos): ?>
                <th>Vendedor</th>
                <?php endif; ?>
                <th>Destino</th>
                <th>Cliente</th>
                <th>Ruta</th>
                <th>Fecha Salida</th>
                <th>Estado</th>
                <th>Fecha Registro</th>
                <th>Productos</th>
                <th>Observaciones</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (mysql_num_rows($result_pedidos) > 0) {
                while ($pedido = mysql_fetch_assoc($result_pedidos)) {
                    echo "<tr>";
                    echo "<td>" . $pedido['folio'] . "</td>";
                    
                    // Si estamos exportando todos los pedidos, mostrar también el vendedor
                    if ($exportar_todos) {
                        echo "<td>" . $pedido['vendedor'] . "</td>";
                    }
                    
                    echo "<td>" . $pedido['destino'] . "</td>";
                    echo "<td>" . $pedido['cliente'] . "</td>";
                    echo "<td>" . $pedido['ruta'] . "</td>";
                    echo "<td>" . $pedido['fecha_salida'] . "</td>";
                    
                    // Mostrar estado textual
                    $estado = "";
                    switch ($pedido['status']) {
                        case 10: $estado = "Pendiente"; break;
                        case 20: $estado = "Cancelado"; break;
                        case 30: $estado = "En Almacén"; break;
                        case 40: $estado = "Entregado Completo"; break;
                        case 50: $estado = "Entregado Incompleto"; break;
                        default: $estado = "Desconocido";
                    }
                    echo "<td>" . $estado . "</td>";
                    
                    echo "<td>" . $pedido['fecha_registro'] . "</td>";
                    
                    // Obtener productos del pedido
                    $sql_productos = "SELECT producto, presentacion, cantidad 
                                    FROM prc_pedidos 
                                    WHERE procesado = 0 AND folio = " . $pedido['folio'] . " 
                                    ORDER BY id";
                    $result_productos = mysql_query($sql_productos) or die("Error al obtener productos: " . mysql_error());
                    
                    echo "<td>";
                    $contador = 1;
                    while ($producto = mysql_fetch_assoc($result_productos)) {
                        echo $contador . ". " . $producto['producto'] . " / " . $producto['presentacion'] . " / Cant: " . $producto['cantidad'] . "<br>";
                        $contador++;
                    }
                    echo "</td>";
                    
                    // Obtener observaciones del pedido
                    $sql_obs = "SELECT observaciones, FR 
                                FROM obs_pedidos 
                                WHERE folio = " . $pedido['folio'] . " 
                                ORDER BY FR";
                    $result_obs = mysql_query($sql_obs) or die("Error al obtener observaciones: " . mysql_error());
                    
                    echo "<td>";
                    $contador = 1;
                    while ($obs = mysql_fetch_assoc($result_obs)) {
                        echo $contador . ". " . date('Y-m-d', strtotime($obs['FR'])) . " - " . $obs['observaciones'] . "<br>";
                        $contador++;
                    }
                    echo "</td>";
                    
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='" . ($exportar_todos ? 10 : 9) . "'>No hay pedidos para exportar</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>
<?php
mysql_close($conectar);
?>