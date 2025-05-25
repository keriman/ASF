<? 
//Reanudamos la sesión 
session_start(); 
//Literalmente la destruimos 
session_destroy(); 

    setcookie("id_usuario_dw", "" , time()-(1));
    setcookie("id_ubicacion_dw", "" , time()-(1));
    setcookie("marca_aleatoria_usuario_dw", "", time()-(1));

//Redireccionamos a index.php (al inicio de sesión) 
header("Location: ../login.php"); 
?>
