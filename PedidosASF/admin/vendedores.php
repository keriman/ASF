<?php
//admin/vendedores.php
include '../conexiones/database.php';

session_start();
if (!isset($_SESSION['username']) && !isset($_SESSION['userid'])) {
    header('Location: ../login.php');
    exit;
}

// Verificar si el usuario tiene permisos de administrador
if ($_SESSION["tipo"] != "admin") {
    header('Location: ../index.php');
    exit;
}

$conectar = mysql_connect($host, $user, $clave);
mysql_select_db($datbase, $conectar);
mysql_set_charset('utf8', $conectar);

$mensaje = '';
$tipo_mensaje = '';

// Procesar formulario de creación/edición
if (isset($_POST['guardar_vendedor'])) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $nombre = trim(strtoupper($_POST['nombre']));
    $telefono = trim($_POST['telefono']);
    
    // Validaciones básicas
    if (empty($id) || empty($nombre)) {
        $mensaje = "El ID y nombre son obligatorios";
        $tipo_mensaje = "danger";
    } else {
        // Verificar si es una actualización o inserción
        $verificar = "SELECT id FROM vendedores WHERE id = $id LIMIT 1";
        $resultado = mysql_query($verificar);
        
        if (mysql_num_rows($resultado) > 0) {
            // Actualizar vendedor existente
            $sql = "UPDATE vendedores SET nombre = '$nombre', telefono = '$telefono' WHERE id = $id";
            if (mysql_query($sql)) {
                $mensaje = "Vendedor actualizado correctamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al actualizar: " . mysql_error();
                $tipo_mensaje = "danger";
            }
        } else {
            // Verificar si ya existe un vendedor con ese nombre
            $verificar_nombre = "SELECT id FROM vendedores WHERE nombre = '$nombre' LIMIT 1";
            $resultado_nombre = mysql_query($verificar_nombre);
            
            if (mysql_num_rows($resultado_nombre) > 0) {
                $mensaje = "Ya existe un vendedor con ese nombre";
                $tipo_mensaje = "danger";
            } else {
                // Insertar nuevo vendedor
                $sql = "INSERT INTO vendedores (id, nombre, telefono) VALUES ($id, '$nombre', '$telefono')";
                if (mysql_query($sql)) {
                    $mensaje = "Vendedor agregado correctamente";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al guardar: " . mysql_error();
                    $tipo_mensaje = "danger";
                }
            }
        }
    }
}

// Procesar eliminación
if (isset($_GET['eliminar']) && !empty($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    
    // Verificar si el vendedor tiene pedidos asociados
    $verificar_pedidos = "SELECT COUNT(*) as total FROM adm_pedidos WHERE vendedor IN (SELECT nombre FROM vendedores WHERE id = $id)";
    $resultado_pedidos = mysql_query($verificar_pedidos);
    $row_pedidos = mysql_fetch_assoc($resultado_pedidos);
    
    if ($row_pedidos['total'] > 0) {
        $mensaje = "No se puede eliminar el vendedor porque tiene pedidos asociados";
        $tipo_mensaje = "danger";
    } else {
        $sql = "DELETE FROM vendedores WHERE id = $id";
        if (mysql_query($sql)) {
            $mensaje = "Vendedor eliminado correctamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al eliminar: " . mysql_error();
            $tipo_mensaje = "danger";
        }
    }
}

// Obtener todos los vendedores para mostrar en la tabla
$sql = "SELECT id, nombre, telefono FROM vendedores ORDER BY nombre";
$resultado = mysql_query($sql);

// Obtener el siguiente ID disponible para autosugerir al crear nuevo vendedor
$sql_max_id = "SELECT MAX(id) as max_id FROM vendedores";
$resultado_max_id = mysql_query($sql_max_id);
$row_max_id = mysql_fetch_assoc($resultado_max_id);
$siguiente_id = ($row_max_id['max_id'] + 1);

// Obtener datos para edición
$vendedor_editar = null;
if (isset($_GET['editar']) && !empty($_GET['editar'])) {
    $id_editar = intval($_GET['editar']);
    $sql_editar = "SELECT id, nombre, telefono FROM vendedores WHERE id = $id_editar LIMIT 1";
    $resultado_editar = mysql_query($sql_editar);
    
    if (mysql_num_rows($resultado_editar) > 0) {
        $vendedor_editar = mysql_fetch_assoc($resultado_editar);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Administración de Vendedores - AgroSantaFe</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../images/ico_agrosantafe.ico">
    <link rel="stylesheet" href="../dist/css/site.min.css">
    <link href="https://netdna.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" rel="stylesheet">
    <link href="http://fonts.googleapis.com/css?family=Open+Sans:400,300,600,800,700,400italic,600italic,700italic,800italic,300italic" rel="stylesheet" type="text/css">
    <script type="text/javascript" src="../dist/js/site.min.js"></script>
    <style type="text/css">
        body { margin-top: 20px; }
        .btn-space { margin-right: 5px; }
        .form-container { margin-bottom: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h1>Administración de Vendedores</h1>
                <a href="../index.php" class="btn btn-primary">Volver al Inicio</a>
                <hr>
                
                <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <?php echo $mensaje; ?>
                </div>
                <?php endif; ?>
                
                <div class="panel panel-default form-container">
                    <div class="panel-heading">
                        <h3 class="panel-title"><?php echo $vendedor_editar ? 'Editar Vendedor' : 'Nuevo Vendedor'; ?></h3>
                    </div>
                    <div class="panel-body">
                        <form class="form-horizontal" method="post" action="">
                            <?php if ($vendedor_editar): ?>
                                <input type="hidden" name="id" value="<?php echo $vendedor_editar['id']; ?>">
                            <?php else: ?>
                                <div class="form-group">
                                    <label for="id" class="col-sm-2 control-label">ID:</label>
                                    <div class="col-sm-4">
                                        <input type="number" class="form-control" id="id" name="id" value="<?php echo $siguiente_id; ?>" required>
                                        <p class="help-block">Ingrese un número único para el vendedor</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label for="nombre" class="col-sm-2 control-label">Nombre:</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" id="nombre" name="nombre" 
                                        value="<?php echo $vendedor_editar ? htmlspecialchars($vendedor_editar['nombre']) : ''; ?>" 
                                        placeholder="Ejemplo: JUAN PEREZ" required>
                                    <p class="help-block">El nombre se guardará en mayúsculas</p>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="telefono" class="col-sm-2 control-label">Teléfono:</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control" id="telefono" name="telefono" 
                                        value="<?php echo $vendedor_editar ? htmlspecialchars($vendedor_editar['telefono']) : ''; ?>" 
                                        placeholder="Ejemplo: 555-1234567">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <div class="col-sm-offset-2 col-sm-10">
                                    <button type="submit" name="guardar_vendedor" class="btn btn-success">
                                        <i class="glyphicon glyphicon-floppy-disk"></i> <?php echo $vendedor_editar ? 'Actualizar Vendedor' : 'Guardar Vendedor'; ?>
                                    </button>
                                    <?php if ($vendedor_editar): ?>
                                        <a href="vendedores.php" class="btn btn-default">Cancelar</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Lista de Vendedores</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Teléfono</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysql_num_rows($resultado) > 0): ?>
                                    <?php while ($row = mysql_fetch_assoc($resultado)): ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                                            <td>
                                                <a href="vendedores.php?editar=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary btn-space">
                                                    <i class="glyphicon glyphicon-pencil"></i> Editar
                                                </a>
                                                <a href="javascript:void(0);" onclick="confirmarEliminar(<?php echo $row['id']; ?>, '<?php echo addslashes($row['nombre']); ?>')" class="btn btn-sm btn-danger">
                                                    <i class="glyphicon glyphicon-trash"></i> Eliminar
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No hay vendedores registrados</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-1.10.2.min.js"></script>
    <script src="https://netdna.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
    <script>
        function confirmarEliminar(id, nombre) {
            if (confirm("¿Está seguro que desea eliminar al vendedor " + nombre + "?")) {
                window.location.href = "vendedores.php?eliminar=" + id;
            }
        }
        
        // Convertir automáticamente el nombre a mayúsculas
        document.getElementById('nombre').addEventListener('blur', function() {
            this.value = this.value.toUpperCase();
        });
    </script>
</body>
</html>