<?php
session_start();
if ( !isset($_SESSION['username']) && !isset($_SESSION['userid']) ){
    header('Location: ../../login.php');
}
if ($_SESSION["tipo"] != "almacen" && $_SESSION["tipo"] != "oficina") {
  header('Location: ../../index.php');
}
date_default_timezone_set('America/Mexico_City');
include '../../conexiones/database.php';
$conectar=mysql_connect($host, $user, $clave);
mysql_select_db($datbase, $conectar);
mysql_set_charset('utf8', $conectar);
$usuario = $_SESSION["username"];
$subs_usuario = $usuario;
$folio = $_POST['ff'];
$producto = $_POST['pp'];
$presentacion = $_POST['pr'];
$cantidad = $_POST['cc'];

// Variables para registrar la modificación
$accion = "";
$modificado = false;

$resultado=mysql_query("SELECT * FROM prc_pedidos WHERE folio = '".$folio."' AND producto = '".$producto."' AND presentacion = '".$presentacion."' LIMIT 1") or die("Problemas en el select: ".mysql_error());
if (mysql_num_rows($resultado)>0) {
    // Obtener el registro actual para comparar
    $row = mysql_fetch_array($resultado);
    $cantidad_anterior = $row['cantidad'];
    
    if ($cantidad > 0) {
        $sql = "UPDATE prc_pedidos SET cantidad='$cantidad', procesado=0, usuario='$subs_usuario' WHERE folio='$folio' AND producto = '$producto' AND presentacion = '$presentacion'";
        if ($cantidad != $cantidad_anterior) {
            $accion = "Actualizado producto: $producto / $presentacion - Cantidad cambiada de $cantidad_anterior a $cantidad";
            $modificado = true;
        }
    } else {
        $sql = "UPDATE prc_pedidos SET procesado=-1, usuario='$subs_usuario' WHERE folio='$folio' AND producto = '$producto' AND presentacion = '$presentacion'";
        $accion = "Eliminado producto: $producto / $presentacion";
        $modificado = true;
    }
} else {
    // Inserción de nuevo producto
    $sql = "INSERT INTO prc_pedidos (folio, producto, presentacion, cantidad, usuario) VALUES ('$folio', '$producto', '$presentacion', '$cantidad', '$subs_usuario')";
    $accion = "Agregado nuevo producto: $producto / $presentacion - Cantidad: $cantidad";
    $modificado = true;
}

// Ejecutar la consulta principal
$result = mysql_query($sql);

// Si hubo modificación, registrar una observación
if ($modificado && !empty($accion)) {
    // Prefijo según el tipo de usuario
    $prefijo = ($_SESSION["tipo"] == "almacen") ? "PENDIENTE: " : "";
    $observacion = $prefijo . $accion;
    
    // Insertar la observación marcada como modificada
    $sql_obs = "INSERT INTO obs_pedidos (folio, observaciones, usuario, modificada) VALUES ('$folio', '$observacion', '$subs_usuario', 1)";
    mysql_query($sql_obs);
}

mysql_close($conectar);
// Redireccionar a la página de edición con el folio
header('Location: index.php?gg='.$folio);