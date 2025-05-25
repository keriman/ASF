<?php
// Suprimir warnings pero mantener errores críticos
error_reporting(E_ERROR | E_PARSE);

// Inicializar variables para evitar el error "Undefined variable"
$bloqueo = isset($_COOKIE["bloqueado"]) ? $_COOKIE["bloqueado"] : "";
$denegado = isset($_COOKIE["denegado"]) ? $_COOKIE["denegado"] : "";

if ($_POST){
  //estamos recibiendo datos por el formulario de autenticación (recibo de $_POST)
  /* A continuación, realizamos la conexión con nuestra base de datos en MySQL */ 
  //conecto con la base de datos
  include '../conexiones/database.php';
  
  // Reemplazar mysql_connect (obsoleto) con mysqli
  $mysqli = new mysqli($host, $user, $clave, $datbase);
  
  // Verificar conexión
  if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
  }
  
  // Sanear la entrada para prevenir inyección SQL
  $usuario = htmlentities($_POST["usuario"]);
  
  //Si existe el usuario, validamos también la contraseña ingresada y el estado del usuario... 
  $sql = "SELECT c.*, c.level, c.role, a.tipo 
          FROM cat_usuarios c 
          LEFT JOIN adm_usuarios a ON c.usuario = a.usuario 
          WHERE c.id_empresa = 5 
          AND c.usuario = '" . $mysqli->real_escape_string($usuario) . "' 
          AND isnull(c.baja)"; 
  
  $result = $mysqli->query($sql);
  
  //Si el usuario y clave ingresado son correctos (y el usuario está activo en la BD), creamos la sesión del mismo. 
  if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    if ($row['codigo'] == htmlentities($_POST["clave"])) {
      session_start(); 
      //Guardamos las variables de sesión
      $_SESSION["autentica"] = "SIP"; 
      $_SESSION["userid"] = $row['id_usuario']; 
      $_SESSION["perfil"] = $row['id_perfil']; 
      $_SESSION["username"] = $usuario;
      $_SESSION['id'] = $_POST['id'];
      $_SESSION['level'] = $row['level'];
      $_SESSION['role'] = $row['role'];
      $_SESSION['tipo'] = $row['tipo']; // Nueva variable de sesión tipo
      
      //ahora debo de ver si el usuario quería memorizar su cuenta en este ordenador
      if (isset($_POST["guardar_clave"]) && $_POST["guardar_clave"] == "1") {
        header("Location: valida.php"); 
        exit;
      } else {
        header("Location: index.php"); 
        exit;
      }
    } else { 
      $bloqueo .= "x";
      setcookie("bloqueado", $bloqueo, time() + 3000); 
      echo "<script>alert('La contraseña del usuario no es correcta.'); window.location.href=\"login.php\"</script>";
    } 
  } else { 
    $denegado .= "x";
    setcookie("denegado", $denegado, time() + 3000); 
    echo "<script>alert('El usuario y/o contraseña incorrecta.'); window.location.href=\"login.php\"</script>";
  } 
  
  // Cerrar la conexión mysqli
  $mysqli->close();
} else {
  header("Location: login.php");
  exit;
}
?>