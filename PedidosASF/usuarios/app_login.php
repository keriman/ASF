<?php
// Script para autenticación de vendedores desde la app móvil
// Compatible con la estructura de la base de datos existente

// Crear un archivo de log específico para esta petición
$log_file = __DIR__ . '/app_login_' . date('Y-m-d') . '.log';
file_put_contents($log_file, "=== NUEVA PETICIÓN DE LOGIN APP: " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
file_put_contents($log_file, "POST: " . print_r($_POST, true) . "\n", FILE_APPEND);

// Iniciar sesión
session_start();

// Obtener los datos de acceso
$usuario = isset($_POST['usuario']) ? $_POST['usuario'] : '';
$clave = isset($_POST['clave']) ? $_POST['clave'] : '';

file_put_contents($log_file, "Usuario recibido: '$usuario', Clave: '$clave'\n", FILE_APPEND);

// Definir usernames de oficina
$oficinas = array('Oficina1', 'Oficina2', 'Oficina3', 'Oficina4');

// Variable para la respuesta
$respuesta = array();

// Verificar si es una oficina de la lista (autenticación especial para la app)
if (in_array($usuario, $oficinas) && $usuario === $clave) {
    file_put_contents($log_file, "Detectado usuario de oficina especial: $usuario\n", FILE_APPEND);
    
    // Autenticación especial para oficinas desde la app
    $_SESSION["autentica"] = "SIP"; 
    $_SESSION["userid"] = md5($usuario); // ID ficticio para la sesión
    $_SESSION["username"] = $usuario;
    $_SESSION["tipo"] = "oficina";
    
    // Respuesta para la app
    $respuesta = array(
        'success' => true,
        'tipo' => 'oficina',
        'message' => 'Autenticación exitosa'
    );
    
    file_put_contents($log_file, "Autenticación especial exitosa para: $usuario\n", FILE_APPEND);
} else {
    // Si no es una oficina especial, intentar autenticación normal contra la base de datos
    file_put_contents($log_file, "Intentando autenticación contra BD para: $usuario\n", FILE_APPEND);
    
    try {
        // Conectar a la base de datos
        include '../../conexiones/database.php';
        $link = mysql_connect($host, $user, $clave);
        
        if (!$link) {
            throw new Exception("Error de conexión a BD: " . mysql_error());
        }
        
        mysql_select_db($datbase, $link);
        
        // Consulta para verificar el usuario
        $sql = "SELECT c.*, c.level, c.role, a.tipo 
                FROM cat_usuarios c 
                LEFT JOIN adm_usuarios a ON c.usuario = a.usuario 
                WHERE c.id_empresa = 5 
                AND c.usuario = '".mysql_real_escape_string($usuario)."' 
                AND isnull(c.baja)";
        
        $resultado = mysql_query($sql, $link);
        
        if (!$resultado) {
            throw new Exception("Error en consulta: " . mysql_error());
        }
        
        $num_filas = mysql_num_rows($resultado);
        
        if ($num_filas > 0 && mysql_result($resultado, 0, 2) == $clave) {
            // Autenticación exitosa con la base de datos
            $_SESSION["autentica"] = "SIP"; 
            $_SESSION["userid"] = mysql_result($resultado, 0, 0); 
            $_SESSION["perfil"] = mysql_result($resultado, 0, 1); 
            $_SESSION["username"] = $usuario;
            $_SESSION['level'] = mysql_result($resultado, 0, 'level');
            $_SESSION['role'] = mysql_result($resultado, 0, 'role');
            $_SESSION['tipo'] = mysql_result($resultado, 0, 'tipo');
            
            // Respuesta para la app
            $respuesta = array(
                'success' => true,
                'tipo' => mysql_result($resultado, 0, 'tipo'),
                'message' => 'Autenticación exitosa'
            );
            
            file_put_contents($log_file, "Autenticación contra BD exitosa para: $usuario\n", FILE_APPEND);
        } else {
            // Credenciales incorrectas
            $respuesta = array(
                'success' => false,
                'tipo' => null,
                'message' => 'Credenciales incorrectas',
                'error' => true
            );
            
            file_put_contents($log_file, "Credenciales incorrectas para: $usuario\n", FILE_APPEND);
        }
        
        mysql_close($link);
    } catch (Exception $e) {
        // Error en la autenticación
        $respuesta = array(
            'success' => false,
            'tipo' => null,
            'message' => 'Error: ' . $e->getMessage(),
            'error' => true
        );
        
        file_put_contents($log_file, "Error en autenticación: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

// Devolver la respuesta como JSON
header('Content-Type: application/json');
$json_respuesta = json_encode($respuesta);
file_put_contents($log_file, "Respuesta JSON: $json_respuesta\n", FILE_APPEND);
file_put_contents($log_file, "=== FIN DE PETICIÓN LOGIN APP ===\n\n", FILE_APPEND);
echo $json_respuesta;
?>