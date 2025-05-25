<?php
   //Reanudamos la sesión 
   @session_start(); 
   $id_user = $_SESSION["usuarioactual"];
  //conecto con la base de datos
   $conn = mysql_connect("localhost","syspak_agrosanta","10X3Kfcc");
   //selecciono la BBDD
   mysql_select_db("syspak_agrosanta",$conn); 

   //debería comprobar si el usuario es correcto
   $ssql = "select * from adm_usuarios where usuario = '" . $id_user . "'";
   //echo $ssql;
   $rs = mysql_query($ssql);
   if (mysql_num_rows($rs)==1){
      //TODO CORRECTO!! He detectado un usuario
      $usuario_encontrado = mysql_fetch_object($rs);
      //ahora debo de ver si el usuario quería memorizar su cuenta en este ordenador
      //es que pidió memorizar el usuario
      //1) creo una marca aleatoria en el registro de este usuario
      //alimentamos el generador de aleatorios
      mt_srand (time());
      //generamos un número aleatorio
      $numero_aleatorio = mt_rand(1000000,999999999);
      //2) meto la marca aleatoria en la tabla de usuario
      $ssql = "update cat_usuario set cookie='$numero_aleatorio' where usuario=" . $usuario_encontrado->usuario;
      mysql_query($ssql);
      //3) ahora meto una cookie en el ordenador del usuario con el identificador del usuario y la cookie aleatoria
      setcookie("id_usuario_dw", $usuario_encontrado->usuario , time() + (86400));
      setcookie("id_ubicacion_dw", $usuario_encontrado->agencia , time() + (86400));
      setcookie("id_caja_dw", $usuario_encontrado->caja , time() + (86400));
      setcookie("marca_aleatoria_usuario_dw", $numero_aleatorio, time() + (86400));

      header ("Location: index.php");
   }
   else {
      echo"<script>alert('El usuario no tiene privilegios para acceder!'); window.location.href=\"login.php\"</script>"; 
   }

   mysql_close($conn);
?>
