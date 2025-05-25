<?php
	session_start();
	if ( !isset($_SESSION['username']) && !isset($_SESSION['userid']) ){
	    header('Location: ../../login.php');
	}
	if ($_SESSION["tipo"] != "oficina") {
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

$resultado=mysql_query("SELECT * FROM prc_pedidos WHERE id=".$id." AND folio=".$folio." AND procesado=0 LIMIT 1") or die("Problemas en el select: ".mysql_error());
	if (mysql_num_rows($resultado)>0) {
		$sql = "UPDATE prc_pedidos SET procesado=-1, usuario='$subs_usuario' WHERE  id=".$id." AND folio=".$folio." AND procesado=0";
	}
	$result = mysql_query($sql);

mysql_close($conectar);

header('Location: index.php?gg='.$folio);
?>