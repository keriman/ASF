<?php
// Incluir la conexión a la base de datos
include '../conexiones/database.php';

// Validación de sesión
session_start();
if (!isset($_SESSION['username']) && !isset($_SESSION['userid'])) {
    header('Location: ../login.php');
    exit;
}

// Sólo permitir a usuarios de almacén y gerencia
if ($_SESSION["tipo"] != "almacen" && $_SESSION["tipo"] != "gerencia") {
    header('Location: ../index.php');
    exit;
}

// Conectar a la base de datos
$conectar = mysql_connect($host, $user, $clave);
mysql_select_db($datbase, $conectar);
mysql_set_charset('utf8', $conectar);

// Determinar el tipo de exportación
// 1. Exportar todos los pedidos
// 2. Exportar pedidos de un vendedor específico
// 3. Exportar pedidos seleccionados

$condicion = "";
$vendedor_nombre = "Todos";

// Caso 1: Exportar todos los pedidos
if (isset($_GET['vendedor']) && $_GET['vendedor'] == 'TODOS') {
    $condicion = "";
    $titulo = "Todos los Pedidos";
}
// Caso 2: Exportar pedidos de un vendedor específico
elseif (isset($_GET['vendedor']) && $_GET['vendedor'] != 'TODOS') {
    $vendedor = htmlspecialchars($_GET['vendedor']);
    $condicion = "WHERE p.vendedor LIKE '%" . $vendedor . "%'";
    $vendedor_nombre = $vendedor;
    $titulo = "Pedidos de " . $vendedor;
}
// Caso 3: Exportar pedidos seleccionados
elseif (isset($_POST['pedidos']) && is_array($_POST['pedidos']) && count($_POST['pedidos']) > 0) {
    $pedidos = array_map('intval', $_POST['pedidos']); // Sanitizar como enteros
    $pedidos_str = implode(',', $pedidos);
    $condicion = "WHERE p.folio IN (" . $pedidos_str . ")";
    
    // Si también se proporcionó un nombre de vendedor
    if (isset($_POST['vendedor']) && !empty($_POST['vendedor'])) {
        $vendedor_nombre = htmlspecialchars($_POST['vendedor']);
        $titulo = "Pedidos Seleccionados de " . $vendedor_nombre;
    } else {
        $titulo = "Pedidos Seleccionados";
    }
} else {
    // Si no se proporcionó ningún criterio válido, redirigir
    header('Location: ' . ($_SESSION["tipo"] == "almacen" ? "paste.php" : "index.php"));
    exit;
}

// Consultar los pedidos según la condición
// Especificamos la tabla para cada columna para evitar ambigüedad
$sql = "SELECT 
            p.folio, 
            p.vendedor, 
            p.destino, 
            p.cliente, 
            p.ruta, 
            p.fecha_salida,
            p.FR as fecha_registro,
            p.status,
            pp.producto,
            pp.presentacion,
            pp.cantidad
        FROM 
            adm_pedidos p
        LEFT JOIN 
            prc_pedidos pp ON p.folio = pp.folio AND pp.procesado = 0
        $condicion
        ORDER BY 
            p.vendedor, p.folio, pp.id";

$resultado = mysql_query($sql) or die("Error en la consulta: " . mysql_error());

// Consultar las observaciones de los pedidos
function obtenerObservaciones($folio) {
    global $conectar; // Aseguramos que la conexión esté disponible dentro de la función
    $sql = "SELECT observaciones FROM obs_pedidos WHERE folio = " . intval($folio) . " ORDER BY FR";
    $res = mysql_query($sql) or die("Error al obtener observaciones: " . mysql_error());
    
    $observaciones = array();
    while ($row = mysql_fetch_array($res)) {
        $observaciones[] = $row['observaciones'];
    }
    
    return implode("\n", $observaciones);
}

// Función para traducir el código de status a texto
function obtenerStatusTexto($status) {
    switch ($status) {
        case 10:
            return "Pendiente";
        case 20:
            return "Cancelado";
        case 30:
            return "En Almacén";
        case 40:
            return "Entregado Completo";
        case 50:
            return "Entregado Incompleto";
        default:
            return "Desconocido";
    }
}

// Crear un array para almacenar los datos organizados
$pedidos_data = array();

// Organizar los datos por folio
while ($row = mysql_fetch_array($resultado)) {
    $folio = $row['folio'];
    
    if (!isset($pedidos_data[$folio])) {
        // Información general del pedido
        $pedidos_data[$folio] = array(
            'folio' => $folio,
            'vendedor' => $row['vendedor'],
            'destino' => $row['destino'],
            'cliente' => $row['cliente'],
            'ruta' => $row['ruta'],
            'fecha_salida' => $row['fecha_salida'],
            'fecha_registro' => $row['fecha_registro'],
            'status' => obtenerStatusTexto($row['status']),
            'observaciones' => obtenerObservaciones($folio),
            'productos' => array()
        );
    }
    
    // Añadir producto si existe
    if (!empty($row['producto'])) {
        $pedidos_data[$folio]['productos'][] = array(
            'producto' => $row['producto'],
            'presentacion' => $row['presentacion'],
            'cantidad' => $row['cantidad']
        );
    }
}

// Configurar cabeceras para exportar a Excel
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename=Pedidos_' . str_replace(' ', '_', $vendedor_nombre) . '_' . date('Y-m-d') . '.xls');
header('Pragma: no-cache');
header('Expires: 0');

// Iniciar el documento Excel
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
  <Author>AgroSantaFe</Author>
  <LastAuthor>AgroSantaFe</LastAuthor>
  <Created><?php echo date('Y-m-d\TH:i:s\Z'); ?></Created>
  <LastSaved><?php echo date('Y-m-d\TH:i:s\Z'); ?></LastSaved>
  <Version>16.00</Version>
 </DocumentProperties>
 <OfficeDocumentSettings xmlns="urn:schemas-microsoft-com:office:office">
  <AllowPNG/>
 </OfficeDocumentSettings>
 <ExcelWorkbook xmlns="urn:schemas-microsoft-com:office:excel">
  <WindowHeight>7920</WindowHeight>
  <WindowWidth>21600</WindowWidth>
  <WindowTopX>32767</WindowTopX>
  <WindowTopY>32767</WindowTopY>
  <ProtectStructure>False</ProtectStructure>
  <ProtectWindows>False</ProtectWindows>
 </ExcelWorkbook>
 <Styles>
  <Style ss:ID="Default" ss:Name="Normal">
   <Alignment ss:Vertical="Bottom"/>
   <Borders/>
   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000"/>
   <Interior/>
   <NumberFormat/>
   <Protection/>
  </Style>
  <Style ss:ID="s62">
   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#FFFFFF" ss:Bold="1"/>
   <Interior ss:Color="#4F81BD" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="s63">
   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000" ss:Bold="1"/>
   <Interior ss:Color="#C5D9F1" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="s64">
   <Alignment ss:Vertical="Top" ss:WrapText="1"/>
  </Style>
 </Styles>
 <Worksheet ss:Name="Pedidos">
  <Table ss:ExpandedColumnCount="10" ss:ExpandedRowCount="<?php echo count($pedidos_data) * 4 + 1; ?>" x:FullColumns="1" x:FullRows="1" ss:DefaultColumnWidth="65" ss:DefaultRowHeight="15">
   <Column ss:Width="60"/>
   <Column ss:Width="100"/>
   <Column ss:Width="100"/>
   <Column ss:Width="100"/>
   <Column ss:Width="100"/>
   <Column ss:Width="80"/>
   <Column ss:Width="80"/>
   <Column ss:Width="80"/>
   <Column ss:Width="100"/>
   <Column ss:Width="200"/>
   
   <Row ss:AutoFitHeight="0">
    <Cell ss:StyleID="s62"><Data ss:Type="String">Folio</Data></Cell>
    <Cell ss:StyleID="s62"><Data ss:Type="String">Vendedor</Data></Cell>
    <Cell ss:StyleID="s62"><Data ss:Type="String">Destino</Data></Cell>
    <Cell ss:StyleID="s62"><Data ss:Type="String">Cliente</Data></Cell>
    <Cell ss:StyleID="s62"><Data ss:Type="String">Ruta</Data></Cell>
    <Cell ss:StyleID="s62"><Data ss:Type="String">Fecha Registro</Data></Cell>
    <Cell ss:StyleID="s62"><Data ss:Type="String">Fecha Salida</Data></Cell>
    <Cell ss:StyleID="s62"><Data ss:Type="String">Status</Data></Cell>
    <Cell ss:StyleID="s62"><Data ss:Type="String">Productos</Data></Cell>
    <Cell ss:StyleID="s62"><Data ss:Type="String">Observaciones</Data></Cell>
   </Row>
   
   <?php 
   $row = 2;
   foreach ($pedidos_data as $pedido) { 
       // Calcular el número de filas que ocupará este pedido (al menos 1)
       $num_productos = count($pedido['productos']);
       $rows_needed = max(1, $num_productos);
       
       // Formatear la lista de productos
       $productos_texto = '';
       foreach ($pedido['productos'] as $producto) {
           $productos_texto .= $producto['producto'] . ' - ' . $producto['presentacion'] . ' - Cant: ' . $producto['cantidad'] . "\n";
       }
   ?>
   <Row ss:AutoFitHeight="0" ss:Height="<?php echo $rows_needed * 15; ?>">
    <Cell><Data ss:Type="String"><?php echo $pedido['folio']; ?></Data></Cell>
    <Cell><Data ss:Type="String"><?php echo $pedido['vendedor']; ?></Data></Cell>
    <Cell><Data ss:Type="String"><?php echo $pedido['destino']; ?></Data></Cell>
    <Cell><Data ss:Type="String"><?php echo $pedido['cliente']; ?></Data></Cell>
    <Cell><Data ss:Type="String"><?php echo $pedido['ruta']; ?></Data></Cell>
    <Cell><Data ss:Type="String"><?php echo $pedido['fecha_registro']; ?></Data></Cell>
    <Cell><Data ss:Type="String"><?php echo $pedido['fecha_salida']; ?></Data></Cell>
    <Cell><Data ss:Type="String"><?php echo $pedido['status']; ?></Data></Cell>
    <Cell ss:StyleID="s64"><Data ss:Type="String"><?php echo $productos_texto; ?></Data></Cell>
    <Cell ss:StyleID="s64"><Data ss:Type="String"><?php echo $pedido['observaciones']; ?></Data></Cell>
   </Row>
   <?php $row++; } ?>
  </Table>
  <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
   <PageSetup>
    <Header x:Margin="0.3"/>
    <Footer x:Margin="0.3"/>
    <PageMargins x:Bottom="0.75" x:Left="0.7" x:Right="0.7" x:Top="0.75"/>
   </PageSetup>
   <Selected/>
   <FreezePanes/>
   <FrozenNoSplit/>
   <SplitHorizontal>1</SplitHorizontal>
   <TopRowBottomPane>1</TopRowBottomPane>
   <ActivePane>2</ActivePane>
   <Panes>
    <Pane>
     <Number>3</Number>
    </Pane>
    <Pane>
     <Number>2</Number>
     <ActiveRow>0</ActiveRow>
    </Pane>
   </Panes>
   <ProtectObjects>False</ProtectObjects>
   <ProtectScenarios>False</ProtectScenarios>
  </WorksheetOptions>
 </Worksheet>
</Workbook>