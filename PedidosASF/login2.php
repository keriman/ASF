<?php
//conecto con la base de datos
include 'conexiones/database.php';
$conn = mysql_connect($host,$user,$clave);


//selecciono la BBDD
mysql_select_db($datbase,$conn); 

//primero tengo que ver si el usuario está memorizado en una cookie
if (isset($_COOKIE["id_usuario_dw"]) && isset($_COOKIE["marca_aleatoria_usuario_dw"])){
   //Tengo cookies memorizadas
   //además voy a comprobar que esas variables no estén vacías
   if ($_COOKIE["id_usuario_dw"] != "" || $_COOKIE["marca_aleatoria_usuario_dw"]!=""){
      //Voy a ver si corresponden con algún usuario
      $ssql = "select * from adm_usuarios where usuario='".$_COOKIE["id_usuario_dw"]."' and cookie ='".$_COOKIE["marca_aleatoria_usuario_dw"]."' and cookie <>''";
      $rs = mysql_query($ssql);
      if (mysql_num_rows($rs)==1){

        $usuario_encontrado = mysql_fetch_object($rs);

  	   	session_start(); 
  	    //Guardamos dos variables de sesión que nos auxiliará para saber si se está o no "logueado" un usuario 
  	    $_SESSION["autentica"] = "SIP"; 
  		$_SESSION["username"] = $usuario_encontrado->usuario; 

  	    //nombre del usuario logueado. 
  	    //Direccionamos a nuestra página principal del sistema. 
  	    header ("Location: usuarios/index.php"); 
      }
   }
}

?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>AgroSantaFe</title>
    <meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no">
		<link rel="shortcut icon" href="inicio/images/ico_agrosantafe.ico"> 
    <link rel="bookmark" href="inicio/images/ico_agrosantafe.ico"/>
    <!-- site css -->
    <link rel="stylesheet" href="dist/css/site.min.css">
    <link href="http://fonts.googleapis.com/css?family=Open+Sans:400,300,600,800,700,400italic,600italic,700italic,800italic,300italic" rel="stylesheet" type="text/css">
    <!-- <link href='http://fonts.googleapis.com/css?family=Lato:300,400,700' rel='stylesheet' type='text/css'> -->
    <!-- HTML5 shim, for IE6-8 support of HTML5 elements. All other JS at the end of file. -->
    <!--[if lt IE 9]>
      <script src="js/html5shiv.js"></script>
      <script src="js/respond.min.js"></script>
    <![endif]-->
    <script type="text/javascript" src="dist/js/site.min.js"></script>
    <style>
      body {
        padding-top: 40px;
        padding-bottom: 40px;
        background-color: #303641;
        color: #C1C3C6
      }
    </style>
  </head>
  <body>
    <div class="container">
      <form class="form-signin" role="form" action="usuarios/control.php" method="post">
        <h3 class="form-signin-heading">Ingrese sus datos</h3>
        <div class="form-group">
          <div class="input-group">
            <div class="input-group-addon">
              <i class="glyphicon glyphicon-user"></i>
            </div>
            <input type="text" class="form-control" name="usuario" id="usuario" placeholder="usuario" autocomplete="off" required="required"/>
          </div>
        </div>

        <div class="form-group">
          <div class="input-group">
            <div class="input-group-addon">
              <i class=" glyphicon glyphicon-lock "></i>
            </div>
            <input type="password" class="form-control" name="clave" id="clave" placeholder="Password" autocomplete="off" required="required"/>
          </div>
        </div>
<!--
        <label class="checkbox">
          <input type="checkbox" value="remember-me"> &nbsp recordar usuario
        </label>
-->
        <button class="btn btn-lg btn-primary btn-block" type="submit">Ingresar</button>
        <div class="text-center" style="margin-top: 15px;">
          <a href="registro.php" style="color: #303641;">¿No tienes una cuenta? Regístrate</a>
        </div>
      </form>
    </div>
    <div class="clearfix"></div>
    <br><br>
    <!--footer-->
  </body>
</html>