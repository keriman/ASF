<?php
/**
 * select_vendedores.php
 * Componente reutilizable para mostrar un select con todos los vendedores
 * 
 * Parámetros:
 * - $nombre_campo: Nombre del input select (default: 'vendedor')
 * - $id_campo: ID del input select (default: 'vendedor')
 * - $valor_seleccionado: Valor preseleccionado (default: '')
 * - $clase_adicional: Clases CSS adicionales (default: '')
 * - $requerido: Si el campo es obligatorio (default: true)
 */

if (!function_exists('generar_select_vendedores')) {
    function generar_select_vendedores($nombre_campo = 'vendedor', $id_campo = 'vendedor', $valor_seleccionado = '', 
                                     $clase_adicional = '', $requerido = true) {
        global $host, $user, $clave, $datbase;
        
        // Verificar si ya hay una conexión activa
        $conectar_local = false;
        if (!isset($GLOBALS['conectar']) || !is_resource($GLOBALS['conectar'])) {
            $conectar = mysql_connect($host, $user, $clave);
            mysql_select_db($datbase, $conectar);
            mysql_set_charset('utf8', $conectar);
            $conectar_local = true;
        }
        
        // Consultar vendedores de la tabla
        $query = "SELECT id, nombre FROM vendedores_quinagro ORDER BY nombre";
        $resultado = mysql_query($query);
        
        // Generar el HTML del select
        $html = '<select name="' . $nombre_campo . '" id="' . $id_campo . '" class="form-control ' . $clase_adicional . '"';
        if ($requerido) {
            $html .= ' required';
        }
        $html .= '>';
        $html .= '<option value="">-- Seleccione un vendedor --</option>';
        
        if ($resultado && mysql_num_rows($resultado) > 0) {
            while ($row = mysql_fetch_assoc($resultado)) {
                $selected = ($valor_seleccionado == $row['nombre']) ? ' selected' : '';
                $html .= '<option value="' . htmlspecialchars($row['nombre']) . '"' . $selected . '>' . 
                         htmlspecialchars($row['nombre']) . '</option>';
            }
        }
        
        $html .= '</select>';
        
        // Cerrar conexión local si fue creada en esta función
        if ($conectar_local) {
            mysql_close($conectar);
        }
        
        return $html;
    }
}

/**
 * Función para obtener un array con todos los vendedores
 */
if (!function_exists('obtener_vendedores')) {
    function obtener_vendedores() {
        global $host, $user, $clave, $datbase;
        
        // Verificar si ya hay una conexión activa
        $conectar_local = false;
        if (!isset($GLOBALS['conectar']) || !is_resource($GLOBALS['conectar'])) {
            $conectar = mysql_connect($host, $user, $clave);
            mysql_select_db($datbase, $conectar);
            mysql_set_charset('utf8', $conectar);
            $conectar_local = true;
        }
        
        // Consultar vendedores
        $query = "SELECT id, nombre, telefono FROM vendedores_quinagro ORDER BY nombre";
        $resultado = mysql_query($query);
        
        $vendedores = array();
        
        if ($resultado && mysql_num_rows($resultado) > 0) {
            while ($row = mysql_fetch_assoc($resultado)) {
                $vendedores[] = $row;
            }
        }
        
        // Cerrar conexión local si fue creada en esta función
        if ($conectar_local) {
            mysql_close($conectar);
        }
        
        return $vendedores;
    }
}

/**
 * Función para obtener datos de un vendedor específico por su nombre
 */
if (!function_exists('obtener_vendedor_por_nombre')) {
    function obtener_vendedor_por_nombre($nombre) {
        global $host, $user, $clave, $datbase;
        
        // Verificar si ya hay una conexión activa
        $conectar_local = false;
        if (!isset($GLOBALS['conectar']) || !is_resource($GLOBALS['conectar'])) {
            $conectar = mysql_connect($host, $user, $clave);
            mysql_select_db($datbase, $conectar);
            mysql_set_charset('utf8', $conectar);
            $conectar_local = true;
        }
        
        // Consultar vendedor
        $nombre = mysql_real_escape_string($nombre);
        $query = "SELECT id, nombre, telefono FROM vendedores_quinagro WHERE nombre = '$nombre' LIMIT 1";
        $resultado = mysql_query($query);
        
        $vendedor = null;
        
        if ($resultado && mysql_num_rows($resultado) > 0) {
            $vendedor = mysql_fetch_assoc($resultado);
        }
        
        // Cerrar conexión local si fue creada en esta función
        if ($conectar_local) {
            mysql_close($conectar);
        }
        
        return $vendedor;
    }
}

/**
 * Función para verificar si un vendedor existe por su nombre
 */
if (!function_exists('existe_vendedor')) {
    function existe_vendedor($nombre) {
        global $host, $user, $clave, $datbase;
        
        // Verificar si ya hay una conexión activa
        $conectar_local = false;
        if (!isset($GLOBALS['conectar']) || !is_resource($GLOBALS['conectar'])) {
            $conectar = mysql_connect($host, $user, $clave);
            mysql_select_db($datbase, $conectar);
            mysql_set_charset('utf8', $conectar);
            $conectar_local = true;
        }
        
        // Consultar vendedor
        $nombre = mysql_real_escape_string($nombre);
        $query = "SELECT id FROM vendedores_quinagro WHERE nombre = '$nombre' LIMIT 1";
        $resultado = mysql_query($query);
        
        $existe = ($resultado && mysql_num_rows($resultado) > 0);
        
        // Cerrar conexión local si fue creada en esta función
        if ($conectar_local) {
            mysql_close($conectar);
        }
        
        return $existe;
    }
}

/**
 * Ejemplo de uso:
 * 
 * include 'select_vendedores.php';
 * 
 * // Para generar un select simple:
 * echo generar_select_vendedores();
 * 
 * // Con parámetros personalizados:
 * echo generar_select_vendedores('vendedor_id', 'vendedor_select', 'JUAN PEREZ', 'mi-clase', false);
 * 
 * // Para obtener un array con todos los vendedores:
 * $vendedores = obtener_vendedores();
 * foreach ($vendedores as $vendedor) {
 *     echo $vendedor['nombre'] . ' - ' . $vendedor['telefono'] . '<br>';
 * }
 * 
 * // Para verificar si un vendedor existe:
 * if (existe_vendedor('JUAN PEREZ')) {
 *     echo 'El vendedor existe';
 * } else {
 *     echo 'El vendedor no existe';
 * }
 */
?>