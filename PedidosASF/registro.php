<?php
//conecto con la base de datos
include 'conexiones/database.php';
$conn = mysql_connect($host,$user,$clave);
mysql_select_db($datbase,$conn); 

if ($_POST) {
    // Validaciones básicas
    $errores = array();
    
    // Limpieza y validación de inputs
    $usuario = htmlentities(trim($_POST['usuario']));
    $clave = htmlentities(trim($_POST['clave']));
    $confirmar_clave = htmlentities(trim($_POST['confirmar_clave']));
    $tipo = htmlentities(trim($_POST['tipo']));
    
    // Validar que el usuario no exista
    $sql = "SELECT usuario FROM cat_usuarios WHERE usuario = '$usuario'";
    $result = mysql_query($sql);
    
    if (mysql_num_rows($result) > 0) {
        $errores[] = "El nombre de usuario ya existe";
    }
    
    // Validar contraseña
    if ($clave !== $confirmar_clave) {
        $errores[] = "Las contraseñas no coinciden";
    }
    
    if (strlen($clave) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres";
    }
    
    // Si no hay errores, proceder con el registro
    if (empty($errores)) {
        $sql = "INSERT INTO cat_usuarios (usuario, codigo, id_empresa, id_perfil) 
                VALUES ('$usuario', '$clave', 5, 1)";
                
        $sql2 = "INSERT INTO adm_usuarios (usuario, tipo, status) 
                 VALUES ('$usuario', '$tipo', 'activo')";
        
        if (mysql_query($sql) && mysql_query($sql2)) {
            echo "<script>alert('Registro exitoso. Por favor inicie sesión.'); 
                  window.location.href='login.php';</script>";
        } else {
            echo "<script>alert('Error al registrar usuario.');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Registro - AgroSantaFe</title>
    <meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no">
    <link rel="shortcut icon" href="inicio/images/ico_agrosantafe.ico"> 
    <link rel="bookmark" href="inicio/images/ico_agrosantafe.ico"/>
    <link rel="stylesheet" href="dist/css/site.min.css">
    <link href="http://fonts.googleapis.com/css?family=Open+Sans:400,300,600,800,700,400italic,600italic,700italic,800italic,300italic" rel="stylesheet" type="text/css">
    <script type="text/javascript" src="/syspak/dist/js/site.min.js"></script>
    <style>
      body {
        padding-top: 40px;
        padding-bottom: 40px;
        background-color: #303641;
        color: #C1C3C6
      }
      .error {
        color: #ff6b6b;
        margin-bottom: 10px;
      }
    </style>
  </head>
  <body>
    <div class="container">
      <form class="form-signin" role="form" action="registro.php" method="post">
        <h3 class="form-signin-heading">Registro de Usuario</h3>
        
        <?php if (!empty($errores)): ?>
            <div class="error">
                <?php foreach($errores as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-group">
          <div class="input-group">
            <div class="input-group-addon">
              <i class="glyphicon glyphicon-user"></i>
            </div>
            <input type="text" class="form-control" name="usuario" id="usuario" 
                   placeholder="Usuario" autocomplete="off" required="required"/>
          </div>
        </div>

        <div class="form-group">
          <div class="input-group">
            <div class="input-group-addon">
              <i class="glyphicon glyphicon-lock"></i>
            </div>
            <input type="password" class="form-control" name="clave" id="clave" 
                   placeholder="Contraseña" autocomplete="off" required="required"/>
          </div>
        </div>

        <div class="form-group">
          <div class="input-group">
            <div class="input-group-addon">
              <i class="glyphicon glyphicon-lock"></i>
            </div>
            <input type="password" class="form-control" name="confirmar_clave" id="confirmar_clave" 
                   placeholder="Confirmar Contraseña" autocomplete="off" required="required"/>
          </div>
        </div>

        <div class="form-group">
          <div class="input-group">
            <div class="input-group-addon">
              <i class="glyphicon glyphicon-briefcase"></i>
            </div>
            <select class="form-control" name="tipo" id="tipo" required="required">
              <option value="">Seleccione tipo de usuario</option>
              <option value="almacen">Almacén</option>
              <option value="gerencia">Gerencia</option>
              <option value="oficina">Oficina</option>
            </select>
          </div>
        </div>

        <button class="btn btn-lg btn-primary btn-block" type="submit">Registrar</button>
        <div class="text-center" style="margin-top: 15px;">
          <a href="login.php" style="color: #C1C3C6;">¿Ya tienes una cuenta? Inicia sesión</a>
        </div>
      </form>
    </div>
  </body>
</html>