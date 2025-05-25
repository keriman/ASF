<?php
session_start();
if ( !isset($_SESSION['username']) && !isset($_SESSION['userid']) ){
    header('Location: ../login.php');
} else {
		if ($_SESSION["tipo"] == "almacen") {
			header('Location: ../almacen/index.php');
		} elseif ($_SESSION["tipo"] == "gerencia") {
			header('Location: ../gerencia/index.php');
		} elseif ($_SESSION["tipo"] == "oficina") {
			header('Location: ../pedidos/index.php');
		} else {
			header('Location: ../login.php');
		}
}
?>