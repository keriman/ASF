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

// Definir usernames de oficina - Añadimos Quinagro a la lista de oficinas permitidas
$oficinas = array('Oficina1', 'Oficina2', 'Oficina3', 'Oficina4', 'Oficina5', 'Quinagro');

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
        require_once('../../pages/conexion.php'); // Incluimos el archivo de conexión
        
        // En lugar de verificar variables antiguas, usamos directamente la conexión PDO
        // que ya está establecida en el archivo conexion.php
        
        // Consulta para verificar el usuario usando PDO en lugar de mysql_*
        $sql = "SELECT c.*, c.level, c.role, a.tipo 
                FROM cat_usuarios c 
                LEFT JOIN adm_usuarios a ON c.usuario = a.usuario 
                WHERE c.id_empresa = 5 
                AND c.usuario = :usuario 
                AND isnull(c.baja)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':usuario', $usuario, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($usuario_data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Verificar la contraseña
            if ($usuario_data['clave'] == $clave) {
                // Autenticación exitosa con la base de datos
                $_SESSION["autentica"] = "SIP"; 
                $_SESSION["userid"] = $usuario_data['id']; 
                $_SESSION["perfil"] = $usuario_data['perfil']; 
                $_SESSION["username"] = $usuario;
                $_SESSION['level'] = $usuario_data['level'];
                $_SESSION['role'] = $usuario_data['role'];
                $_SESSION['tipo'] = $usuario_data['tipo'];
                
                // Respuesta para la app
                $respuesta = array(
                    'success' => true,
                    'tipo' => $usuario_data['tipo'],
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
        } else {
            // Usuario no encontrado
            $respuesta = array(
                'success' => false,
                'tipo' => null,
                'message' => 'Usuario no encontrado',
                'error' => true
            );
            
            file_put_contents($log_file, "Usuario no encontrado: $usuario\n", FILE_APPEND);
        }
    } catch (PDOException $e) {
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
header('Access-Control-Allow-Origin: http://127.0.0.1');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Max-Age: 3600');

$json_respuesta = json_encode($respuesta);
file_put_contents($log_file, "Respuesta JSON: $json_respuesta\n", FILE_APPEND);
file_put_contents($log_file, "=== FIN DE PETICIÓN LOGIN APP ===\n\n", FILE_APPEND);
echo $json_respuesta;
?>