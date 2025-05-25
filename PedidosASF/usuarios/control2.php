<?php
if ($_POST){
  //estamos recibiendo datos por el formulario de autenticación (recibo de $_POST)
  /* A continuación, realizamos la conexión con nuestra base de datos en MySQL */ 
  //conecto con la base de datos
  include '../conexiones/database.php';
  $link = mysql_connect($host,$user,$clave); 
  mysql_select_db($datbase, $link); 
  
  file_put_contents('login_log.txt', 
    date('Y-m-d H:i:s') . " - Usuario: " . $_POST["usuario"] . 
    " - Clave: " . $_POST["clave"] . "\n", 
    FILE_APPEND);
  
  //Si existe el usuario, validamos también la contraseña ingresada y el estado del usuario... 
  $sql = "SELECT c.*, c.level, c.role, a.tipo 
          FROM cat_usuarios c 
          LEFT JOIN adm_usuarios a ON c.usuario = a.usuario 
          WHERE c.id_empresa = 5 
          AND c.usuario = '".htmlentities($_POST["usuario"])."' 
          AND isnull(c.baja)"; 
  
  $myclave = mysql_query($sql, $link); 
  $nmyclave = mysql_num_rows($myclave); 
  
  //Si el usuario y clave ingresado son correctos (y el usuario está activo en la BD), creamos la sesión del mismo. 
  if($nmyclave != 0){ 
    if(mysql_result($myclave,0,2) == htmlentities($_POST["clave"])){
      session_start(); 
      //Guardamos las variables de sesión
      $_SESSION["autentica"] = "SIP"; 
      $_SESSION["userid"] = mysql_result($myclave,0,0); 
      $_SESSION["perfil"] = mysql_result($myclave,0,1); 
      $_SESSION["username"] = htmlentities($_POST["usuario"]);
      $_SESSION['id'] = $_POST['id'];
      $_SESSION['level'] = mysql_result($myclave,0,'level');
      $_SESSION['role'] = mysql_result($myclave,0,'role');
      $_SESSION['tipo'] = mysql_result($myclave,0,'tipo'); // Nueva variable de sesión tipo
      
      //ahora debo de ver si el usuario quería memorizar su cuenta en este ordenador
      if ($_POST["guardar_clave"]=="1"){
        header ("Location: valida.php"); 
      } else {
        header ("Location: ../index.php"); 
      }
    } else { 
      $bloqueo .= "x";
      setcookie("bloqueado", $bloqueo, time() + 3000); 
    } 
  } else { 
    $denegado .= "x";
    setcookie("denegado", $denegado, time() + 3000); 
  } 
  mysql_close($link);
} else {
  //header ("Location: ../login.php");
}
?>
