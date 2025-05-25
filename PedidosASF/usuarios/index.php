<?php
//conecto con la base de datos
include '../conexiones/database.php';
$conn = mysql_connect($host,$user,$clave);
//$conn = mysql_connect("localhost","syspak_agrosanta","10X3Kfcc");
//selecciono la BBDD
//mysql_select_db("syspak_agrosanta",$conn); 
mysql_select_db($datbase,$conn); 
	//Reanudamos la sesión 
	session_start();
	//Validamos si existe realmente una sesión activa o no 
//	echo("Usuario: ".$_SESSION["username"]."<br>");
	if($_SESSION["autentica"] != "SIP")
	{ 
	  //Si no hay sesión activa, lo direccionamos al index.php (inicio de sesión) 
	  header("Location: ../login.php"); 
	  exit(); 
	} 

	if (isset($_SESSION["username"])) {
      $ssql = "select * from adm_usuarios where status = 'activo' and usuario='".$_SESSION["username"]."'";
//      echo($ssql);
      $rs = mysql_query($ssql);
      if (mysql_num_rows($rs)==1){

        $usuario_reg = mysql_fetch_object($rs);

	    //Guardamos dos variables de sesión que nos auxiliará para saber si se está o no "logueado" un usuario 
		$_SESSION["tipo"] = $usuario_reg->tipo;
		}
	}
	
	if ($_SESSION["tipo"] == "almacen") {
		header('Location: ../almacen/index.php');
	} elseif ($_SESSION["tipo"] == "gerencia") {
		header('Location: ../gerencia/index.php');
	} elseif ($_SESSION["tipo"] == "oficina") {
		header('Location: ../pedidos/index.php');
	} else {
		header('Location: ../login.php');
	}
?>