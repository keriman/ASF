<?php
	include '../../conexiones/database.php';
	session_start();
	if ( !isset($_SESSION['username']) && !isset($_SESSION['userid']) ){
	    header('Location: ../../login.php');
	}
	if ($_SESSION["tipo"] != "almacen") {
	  header('Location: ../../index.php');
	}
	$tab = isset($_GET['tab']) ? $_GET['tab'] : 'todos';


	$conectar=mysql_connect($host, $user, $clave);
	mysql_select_db($datbase, $conectar);
	mysql_set_charset('utf8', $conectar);

	date_default_timezone_set ("America/Mexico_City");

	$usuario = $_SESSION["username"];
	$subs_usuario = $usuario;
	$fecha = date("Y/m/d");
	$status = 30;
	$usuario = $subs_usuario;

	if (htmlentities($_GET['gg']) != '') {
		$subs_folio = htmlentities($_GET['gg']);

		// Verificamos el folio
		$buscar = "SELECT folio, status FROM adm_pedidos WHERE folio = ".$subs_folio." LIMIT 1";
		//echo("<br/>".$buscar);
		$resultado=mysql_query($buscar);

		if (mysql_num_rows($resultado) != 0) {
			$clavprod = mysql_query($buscar);
			if ($row = mysql_fetch_array($clavprod)) { 
				$folio = $row['folio'];
				$status = $row['status'];

				if ($status == 30) {
						$sql = "UPDATE adm_pedidos SET status=50 WHERE folio='$folio'";
						$result = mysql_query($sql);
						// agregar comentarios
						$comenta = "Entregado incompleto";
						$sql = "INSERT INTO obs_pedidos (folio, observaciones, usuario) VALUES ('$folio', '$comenta', '$subs_usuario')";
						$obser = mysql_query($sql);
				}
				header('Location: ../index.php?gg='.$folio);
			}
		}
	}
	header('Location: ../index.php?');
?>