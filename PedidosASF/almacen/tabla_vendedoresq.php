<?php
// /PedidosASF/almacen/tabla_vendedoresq.php

// Incluir el archivo de conexión a la base de datos
include($_SERVER['DOCUMENT_ROOT'] . '/PedidosASF/conexiones/database.php');

// Conectar a la base de datos
$conectar = mysql_connect($host, $user, $clave);
mysql_select_db($datbase, $conectar);
mysql_set_charset('utf8', $conectar);

// Determinar el orden de la tabla
$order_by = isset($_GET['order_by']) ? $_GET['order_by'] : 'id';
$order_dir = isset($_GET['order_dir']) ? $_GET['order_dir'] : 'ASC';

// Validar los valores para evitar inyección SQL
if (!in_array($order_by, array('id', 'nombre', 'telefono'))) {
    $order_by = 'id';
}
if (!in_array($order_dir, array('ASC', 'DESC'))) {
    $order_dir = 'ASC';
}

// Obtener todos los vendedores con el orden especificado
$sql = "SELECT id, nombre, telefono FROM vendedores_quinagro ORDER BY $order_by $order_dir";
$resultado = mysql_query($sql);

// Obtener el siguiente ID disponible
$sql_max_id = "SELECT MAX(id) as max_id FROM vendedores";
$resultado_max_id = mysql_query($sql_max_id);
$row_max_id = mysql_fetch_assoc($resultado_max_id);
$siguiente_id = isset($row_max_id['max_id']) ? ($row_max_id['max_id'] + 1) : 1;

// Identificador único para esta instancia de la tabla
$tabla_id = 'tabla_vendedores_' . rand(1000, 9999);

// URL absoluta del procesador AJAX
$ajax_processor_url = 'PedidosASF/almacen/procesar_vendedoresq.php';

// Función para generar enlaces de ordenamiento
function get_sort_link($column, $current_order_by, $current_order_dir) {
    $new_order_dir = ($current_order_by == $column && $current_order_dir == 'ASC') ? 'DESC' : 'ASC';
    
    $icon_class = '';
    if ($current_order_by == $column) {
        $icon_class = ($current_order_dir == 'ASC') ? 'fas fa-sort-up' : 'fas fa-sort-down';
    } else {
        $icon_class = 'fas fa-sort text-muted';
    }
    
    $params = $_GET;
    $params['order_by'] = $column;
    $params['order_dir'] = $new_order_dir;
    $query_string = http_build_query($params);
    
    return '<a href="?' . $query_string . '" class="text-white">' . 
           $column . ' <i class="' . $icon_class . '"></i></a>';
}
?>

<!-- Tarjeta para la tabla de vendedores (AdminLTE) -->
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-user-tag mr-2"></i>Vendedores
        </h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    
    <!-- Alerta para mensajes -->
    <div id="alerta_<?php echo $tabla_id; ?>" class="alert m-2" style="display: none;">
        <button type="button" class="close" onclick="$('#alerta_<?php echo $tabla_id; ?>').hide();">&times;</button>
        <span id="mensaje_alerta_<?php echo $tabla_id; ?>"></span>
    </div>
    
    <div class="card-body">
        <!-- Formulario para agregar nuevo vendedor -->
        <form id="form_nuevo_vendedor_<?php echo $tabla_id; ?>" class="form-inline mb-4">
            <div class="input-group input-group-sm mr-2">
                <div class="input-group-prepend">
                    <span class="input-group-text">ID</span>
                </div>
                <input type="number" class="form-control" id="nuevo_id_<?php echo $tabla_id; ?>" value="<?php echo $siguiente_id; ?>" min="1" required style="width: 70px;">
            </div>
            
            <div class="input-group input-group-sm mr-2">
                <div class="input-group-prepend">
                    <span class="input-group-text">Nombre</span>
                </div>
                <input type="text" class="form-control" id="nuevo_nombre_<?php echo $tabla_id; ?>" placeholder="NOMBRE DEL VENDEDOR" required style="width: 200px;">
            </div>
            
            <div class="input-group input-group-sm mr-2">
                <div class="input-group-prepend">
                    <span class="input-group-text">Teléfono</span>
                </div>
                <input type="text" class="form-control" id="nuevo_telefono_<?php echo $tabla_id; ?>" placeholder="555-1234567" style="width: 120px;">
            </div>
            
            <button type="button" class="btn btn-success btn-sm" onclick="agregarVendedor_<?php echo $tabla_id; ?>()">
                <i class="fas fa-plus"></i> Agregar
            </button>
        </form>
        
        <!-- Tabla de vendedores -->
        <div class="table-responsive">
            <table class="table table-hover table-striped" id="<?php echo $tabla_id; ?>">
                <thead>
                    <tr>
                        <th style="width: 10%"><?php echo get_sort_link('id', $order_by, $order_dir); ?></th>
                        <th style="width: 45%"><?php echo get_sort_link('nombre', $order_by, $order_dir); ?></th>
                        <th style="width: 30%"><?php echo get_sort_link('telefono', $order_by, $order_dir); ?></th>
                        <th style="width: 15%">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resultado && mysql_num_rows($resultado) > 0): ?>
                        <?php while ($row = mysql_fetch_assoc($resultado)): ?>
                            <tr id="fila_<?php echo $tabla_id; ?>_<?php echo $row['id']; ?>">
                                <td><?php echo $row['id']; ?></td>
                                <td>
                                    <span class="editable" 
                                          data-id="<?php echo $row['id']; ?>" 
                                          data-campo="nombre" 
                                          data-tabla="<?php echo $tabla_id; ?>"
                                          title="Haga doble clic para editar">
                                        <?php echo htmlspecialchars($row['nombre']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="editable" 
                                          data-id="<?php echo $row['id']; ?>" 
                                          data-campo="telefono" 
                                          data-tabla="<?php echo $tabla_id; ?>"
                                          title="Haga doble clic para editar">
                                        <?php echo htmlspecialchars($row['telefono']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-danger btn-xs" onclick="eliminarVendedor_<?php echo $tabla_id; ?>(<?php echo $row['id']; ?>, '<?php echo addslashes($row['nombre']); ?>')">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr id="fila_vacia_<?php echo $tabla_id; ?>">
                            <td colspan="4" class="text-center">No hay vendedores registrados</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Estilos para la edición inline adaptados a tema oscuro -->
<style>
    .editable {
        display: block;
        padding: 5px;
        cursor: pointer;
        border: 1px solid transparent;
        border-radius: 3px;
    }
    .editable:hover {
        background-color: rgba(255, 255, 255, 0.1);
        border: 1px dashed #6c757d;
    }
    .editing {
        border: 1px solid #007bff;
        background-color: #343a40;
        color: #fff;
        padding: 5px;
        border-radius: 4px;
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
        outline: 0 none;
        width: 100%;
    }
    
    /* Estilos para iconos de ordenamiento */
    th a {
        text-decoration: none;
        display: block;
        position: relative;
    }
    
    th a i {
        margin-left: 5px;
        font-size: 0.8em;
    }
</style>

<!-- Scripts para la edición en línea -->
<script>
// Función para mostrar alerta
function mostrarAlerta_<?php echo $tabla_id; ?>(tipo, mensaje) {
    var alerta = $('#alerta_<?php echo $tabla_id; ?>');
    alerta.removeClass('alert-success alert-danger').addClass('alert-' + tipo);
    $('#mensaje_alerta_<?php echo $tabla_id; ?>').html(mensaje);
    alerta.show();
    
    // Ocultar después de 5 segundos
    setTimeout(function() {
        alerta.fadeOut();
    }, 5000);
}

// Función para editar en línea
$(document).ready(function() {
    // Detectar doble clic en los elementos editables
    $('.editable[data-tabla="<?php echo $tabla_id; ?>"]').dblclick(function() {
        var elemento = $(this);
        var id = elemento.data('id');
        var campo = elemento.data('campo');
        var valorActual = elemento.text().trim();
        
        // Crear input de edición
        var input = $('<input type="text" class="editing" />');
        input.val(valorActual);
        
        // Reemplazar el span por el input
        elemento.html(input);
        input.focus();
        
        // Manejar la pérdida de foco
        input.blur(function() {
            var nuevoValor = $(this).val().trim();
            
            // Si no ha cambiado, restaurar el valor original
            if (nuevoValor === valorActual) {
                elemento.html(valorActual);
                return;
            }
            
            // Mostrar spinner de carga
            elemento.html('<i class="fas fa-spinner fa-spin"></i> Actualizando...');
            
            // Enviar actualización al servidor con URL absoluta
            $.ajax({
                url: '<?php echo $ajax_processor_url; ?>',
                type: 'POST',
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                data: {
                    action: 'update',
                    id: id,
                    campo: campo,
                    valor: nuevoValor
                },
                success: function(response) {
                    console.log('Respuesta del servidor:', response);
                    if (response && response.status === 'success') {
                        elemento.html(campo === 'nombre' ? nuevoValor.toUpperCase() : nuevoValor);
                        mostrarAlerta_<?php echo $tabla_id; ?>('success', response.message);
                    } else {
                        elemento.html(valorActual);
                        var errorMsg = response && response.message ? response.message : 'Error desconocido';
                        mostrarAlerta_<?php echo $tabla_id; ?>('danger', errorMsg);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error AJAX:', xhr.responseText, status, error);
                    elemento.html(valorActual);
                    mostrarAlerta_<?php echo $tabla_id; ?>('danger', 'Error de conexión al servidor: ' + status);
                }
            });
        });
        
        // Manejar tecla Enter
        input.keypress(function(e) {
            if (e.which === 13) {
                $(this).blur();
                return false;
            }
        });
        
        // Manejar tecla Escape
        input.keydown(function(e) {
            if (e.which === 27) {
                elemento.html(valorActual);
                return false;
            }
        });
    });
    
    // Convertir a mayúsculas el campo nombre cuando se edita
    $('#nuevo_nombre_<?php echo $tabla_id; ?>').blur(function() {
        $(this).val($(this).val().toUpperCase());
    });
});

// Función para agregar nuevo vendedor
function agregarVendedor_<?php echo $tabla_id; ?>() {
    var id = $('#nuevo_id_<?php echo $tabla_id; ?>').val();
    var nombre = $('#nuevo_nombre_<?php echo $tabla_id; ?>').val().toUpperCase();
    var telefono = $('#nuevo_telefono_<?php echo $tabla_id; ?>').val();
    
    if (!id || !nombre) {
        mostrarAlerta_<?php echo $tabla_id; ?>('danger', 'El ID y nombre son obligatorios');
        return;
    }
    
    $.ajax({
        url: '<?php echo $ajax_processor_url; ?>',
        type: 'POST',
        dataType: 'json',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        data: {
            action: 'add',
            id: id,
            nombre: nombre,
            telefono: telefono
        },
        success: function(response) {
            if (response.status === 'success') {
                // Eliminar mensaje de "No hay vendedores"
                $('#fila_vacia_<?php echo $tabla_id; ?>').remove();
                
                // Agregar nueva fila a la tabla
                var nuevaFila = '<tr id="fila_<?php echo $tabla_id; ?>_' + response.id + '">' +
                    '<td>' + response.id + '</td>' +
                    '<td><span class="editable" data-id="' + response.id + '" data-campo="nombre" data-tabla="<?php echo $tabla_id; ?>" title="Haga doble clic para editar">' + response.nombre + '</span></td>' +
                    '<td><span class="editable" data-id="' + response.id + '" data-campo="telefono" data-tabla="<?php echo $tabla_id; ?>" title="Haga doble clic para editar">' + response.telefono + '</span></td>' +
                    '<td><button class="btn btn-danger btn-xs" onclick="eliminarVendedor_<?php echo $tabla_id; ?>(' + response.id + ', \'' + response.nombre + '\')"><i class="fas fa-trash"></i> Eliminar</button></td>' +
                    '</tr>';
                $('#<?php echo $tabla_id; ?> tbody').append(nuevaFila);
                
                // Reiniciar el formulario y actualizar el siguiente ID
                $('#nuevo_id_<?php echo $tabla_id; ?>').val(parseInt(id) + 1);
                $('#nuevo_nombre_<?php echo $tabla_id; ?>').val('');
                $('#nuevo_telefono_<?php echo $tabla_id; ?>').val('');
                
                // Aplicar la funcionalidad de doble clic a los nuevos elementos
                $('.editable[data-tabla="<?php echo $tabla_id; ?>"]').dblclick(function() {
                    var elemento = $(this);
                    var id = elemento.data('id');
                    var campo = elemento.data('campo');
                    var valorActual = elemento.text().trim();
                    
                    var input = $('<input type="text" class="editing" />');
                    input.val(valorActual);
                    
                    elemento.html(input);
                    input.focus();
                    
                    input.blur(function() {
                        var nuevoValor = $(this).val().trim();
                        
                        if (nuevoValor === valorActual) {
                            elemento.html(valorActual);
                            return;
                        }
                        
                        // Mostrar spinner
                        elemento.html('<i class="fas fa-spinner fa-spin"></i> Actualizando...');
                        
                        $.ajax({
                            url: '<?php echo $ajax_processor_url; ?>',
                            type: 'POST',
                            dataType: 'json',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            data: {
                                action: 'update',
                                id: id,
                                campo: campo,
                                valor: nuevoValor
                            },
                            success: function(response) {
                                if (response.status === 'success') {
                                    elemento.html(campo === 'nombre' ? nuevoValor.toUpperCase() : nuevoValor);
                                    mostrarAlerta_<?php echo $tabla_id; ?>('success', response.message);
                                } else {
                                    elemento.html(valorActual);
                                    mostrarAlerta_<?php echo $tabla_id; ?>('danger', response.message);
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Error AJAX:', xhr.responseText, status, error);
                                elemento.html(valorActual);
                                mostrarAlerta_<?php echo $tabla_id; ?>('danger', 'Error de conexión al servidor: ' + status);
                            }
                        });
                    });
                    
                    input.keypress(function(e) {
                        if (e.which === 13) {
                            $(this).blur();
                            return false;
                        }
                    });
                    
                    input.keydown(function(e) {
                        if (e.which === 27) {
                            elemento.html(valorActual);
                            return false;
                        }
                    });
                });
                
                mostrarAlerta_<?php echo $tabla_id; ?>('success', response.message);
            } else {
                mostrarAlerta_<?php echo $tabla_id; ?>('danger', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', xhr.responseText, status, error);
            mostrarAlerta_<?php echo $tabla_id; ?>('danger', 'Error de conexión al servidor: ' + status);
        }
    });
}

// Función para eliminar vendedor
function eliminarVendedor_<?php echo $tabla_id; ?>(id, nombre) {
    if (!confirm('¿Está seguro que desea eliminar al vendedor ' + nombre + '?')) {
        return;
    }
    
    $.ajax({
        url: '<?php echo $ajax_processor_url; ?>',
        type: 'POST',
        dataType: 'json',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        data: {
            action: 'delete',
            id: id
        },
        success: function(response) {
            if (response.status === 'success') {
                // Eliminar la fila de la tabla
                $('#fila_<?php echo $tabla_id; ?>_' + id).remove();
                
                // Si no hay más vendedores, mostrar mensaje
                if ($('#<?php echo $tabla_id; ?> tbody tr').length === 0) {
                    $('#<?php echo $tabla_id; ?> tbody').html('<tr id="fila_vacia_<?php echo $tabla_id; ?>"><td colspan="4" class="text-center">No hay vendedores registrados</td></tr>');
                }
                
                mostrarAlerta_<?php echo $tabla_id; ?>('success', response.message);
            } else {
                mostrarAlerta_<?php echo $tabla_id; ?>('danger', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', xhr.responseText, status, error);
            mostrarAlerta_<?php echo $tabla_id; ?>('danger', 'Error de conexión al servidor: ' + status);
        }
    });
}
</script>