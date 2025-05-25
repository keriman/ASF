<?php
session_start();
if ( !isset($_SESSION['username']) && !isset($_SESSION['userid']) ){
    header('Location: ../../login.php');
}
if ($_SESSION["tipo"] != "oficina" && $_SESSION["tipo"] != "almacen") {
  header('Location: ../../index.php');
}

date_default_timezone_set('America/Mexico_City');
include '../../conexiones/database.php';

$conectar=mysql_connect($host, $user, $clave);
mysql_select_db($datbase, $conectar);
mysql_set_charset('utf8', $conectar);

$usuario = $_SESSION["username"];
$subs_usuario = $usuario;

// Recolectar los datos del formulario
$folio = $_POST['ff'];
$vendedor = $_POST['vv'];
$destino = $_POST['rr'];
$cliente = isset($_POST['cl']) ? $_POST['cl'] : ''; // Verificar si existe
$ruta = isset($_POST['rt']) ? $_POST['rt'] : '';    // Verificar si existe
$fecha_salida = $_POST['fs'];
$observaciones = $_POST['cc'];

// Variable para controlar si hubo cambios
$pedido_modificado = false;
$cambios = "";

// Verificar si el pedido ya existe
$resultado=mysql_query("SELECT * FROM adm_pedidos WHERE folio = '".$folio."' LIMIT 1") or die("Problemas en el select: ".mysql_error());
if (mysql_num_rows($resultado)>0) {
    // El pedido existe, verificar si se modificaron los datos
    $row = mysql_fetch_array($resultado);
    
    // IMPORTANTE: Si el status es 30, mantenerlo, independientemente del tipo de usuario
    if ($row['status'] == 30) {
        $status = 30;
    } else {
        // Si no es 30, usar la regla original según el tipo de usuario
        $status = ($_SESSION["tipo"] == "almacen") ? 30 : 10;
    }
    
    // Comparar los valores anteriores con los nuevos
    if ($row['vendedor'] != $vendedor) {
        $cambios .= "Vendedor cambiado de " . $row['vendedor'] . " a " . $vendedor . ". ";
        $pedido_modificado = true;
    }
    if ($row['destino'] != $destino) {
        $cambios .= "Destino cambiado de " . $row['destino'] . " a " . $destino . ". ";
        $pedido_modificado = true;
    }
    if ($row['cliente'] != $cliente) {
        $cambios .= "Cliente cambiado de " . $row['cliente'] . " a " . $cliente . ". ";
        $pedido_modificado = true;
    }
    if ($row['ruta'] != $ruta) {
        $cambios .= "Ruta cambiada de " . $row['ruta'] . " a " . $ruta . ". ";
        $pedido_modificado = true;
    }
    if ($row['fecha_salida'] != $fecha_salida) {
        $cambios .= "Fecha de salida cambiada de " . $row['fecha_salida'] . " a " . $fecha_salida . ". ";
        $pedido_modificado = true;
    }
    
    // Actualizar un pedido existente
    $sql = "UPDATE adm_pedidos SET vendedor='$vendedor', destino='$destino', cliente='$cliente', ruta='$ruta', fecha_salida='$fecha_salida', status='$status', usuario='$subs_usuario' WHERE folio='$folio'";
} else {
    // Es un pedido nuevo, usar la regla original según el tipo de usuario
    $status = ($_SESSION["tipo"] == "almacen") ? 30 : 10;
    
    // Insertar un nuevo pedido
    $sql = "INSERT INTO adm_pedidos (folio, vendedor, destino, cliente, ruta, fecha_salida, status, usuario) VALUES ('$folio', '$vendedor', '$destino', '$cliente', '$ruta', '$fecha_salida', '$status', '$subs_usuario')";
    $cambios = "Nuevo pedido creado.";
    $pedido_modificado = true;
}

// Ejecutar la consulta principal
$result = mysql_query($sql) or die("Error al guardar el pedido: " . mysql_error());

// Si se modificó el pedido, registrar una observación automática con los cambios
if ($pedido_modificado && !empty($cambios)) {
    $prefijo = ($_SESSION["tipo"] == "oficina") ? "PENDIENTE: " : "";
    $cambio_obs = $prefijo . "Modificación automática: " . $cambios;
    
    // Insertar la observación de los cambios realizados
    $sql_cambio = "INSERT INTO obs_pedidos (folio, observaciones, usuario, modificada) VALUES ('$folio', '$cambio_obs', '$subs_usuario', 1)";
    mysql_query($sql_cambio);
}

// Agregar la observación si existe
if ($observaciones != "") {
    // El prefijo PENDIENTE: ayudará a identificar observaciones pendientes en la lista de almacén
    if ($_SESSION["tipo"] == "oficina") {
        $observaciones = "PENDIENTE: " . $observaciones;
    }
    
    // Marcar la observación como modificada (1) para mostrar la 'M' roja
    $sql_obs = "INSERT INTO obs_pedidos (folio, observaciones, usuario, modificada) VALUES ('$folio', '$observaciones', '$subs_usuario', 1)";
    $result_obs = mysql_query($sql_obs);
}

mysql_close($conectar);

// Redireccionar según el tipo de usuario
if ($_SESSION["tipo"] == "oficina") {
    header('Location: ../index.php');
} else {
    header('Location: ../index.php');
}