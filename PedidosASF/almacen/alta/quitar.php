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

$id = $_GET['dd'];
$folio = $_GET['ff'];

$sql = "UPDATE prc_pedidos SET procesado=-1, usuario='$subs_usuario' WHERE id='$id'";
$result = mysql_query($sql);

mysql_close($conectar);

header('Location: index.php?gg='.$folio);