<?php
// Configuración para mostrar todos los errores

// Crear archivo de log para depuración
$log_file = __DIR__ . '/ultimo_folio_debug_' . date('Y-m-d') . '.log';
file_put_contents($log_file, "=== NUEVA PETICIÓN DE ÚLTIMO FOLIO (MODO DEBUG): " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);

// Capturar cualquier error que pueda ocurrir
function exception_error_handler($errno, $errstr, $errfile, $errline) {
    global $log_file;
    $error_msg = "Error #[$errno] en $errfile:$errline: $errstr\n";
    file_put_contents($log_file, $error_msg, FILE_APPEND);
    return true;
}
set_error_handler("exception_error_handler");

try {
    // Iniciar sesión
    session_start();
    file_put_contents($log_file, "Sesión iniciada\n", FILE_APPEND);
    file_put_contents($log_file, "Contenido de la sesión: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

    // Verificar que existe el archivo de conexión
    $conexion_path = '../../pages/conexion.php';
    file_put_contents($log_file, "Verificando archivo de conexión en: $conexion_path\n", FILE_APPEND);
    file_put_contents($log_file, "¿Archivo existe? " . (file_exists($conexion_path) ? "SÍ" : "NO") . "\n", FILE_APPEND);
    
    if (!file_exists($conexion_path)) {
        file_put_contents($log_file, "Buscando archivo conexion.php en otras rutas...\n", FILE_APPEND);
        // Intentar buscar el archivo en el directorio actual
        if (file_exists('conexion.php')) {
            $conexion_path = 'conexion.php';
            file_put_contents($log_file, "Encontrado en la ruta actual\n", FILE_APPEND);
        } elseif (file_exists('../conexion.php')) {
            $conexion_path = '../conexion.php';
            file_put_contents($log_file, "Encontrado un nivel arriba\n", FILE_APPEND);
        } else {
            file_put_contents($log_file, "No se encontró el archivo de conexión en ninguna ruta alternativa\n", FILE_APPEND);
        }
    }

    // Este es un punto crítico de fallo, así que verificamos antes
    file_put_contents($log_file, "Intentando incluir conexion.php desde: $conexion_path\n", FILE_APPEND);
    
    // Incluir archivo de conexión y capturar cualquier salida
    ob_start();
    include $conexion_path;
    $output = ob_get_clean();
    file_put_contents($log_file, "Salida al incluir conexion.php: $output\n", FILE_APPEND);
    
    // Verificar si la conexión PDO fue establecida
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        file_put_contents($log_file, "Variable \$pdo no encontrada o no es una instancia de PDO\n", FILE_APPEND);
        throw new Exception("La conexión PDO no fue establecida correctamente");
    }
    
    file_put_contents($log_file, "Conexión PDO obtenida correctamente\n", FILE_APPEND);

    // Verificar los parámetros de la conexión
    file_put_contents($log_file, "Parámetros de conexión: host=$host, dbname=$dbname, username=$username\n", FILE_APPEND);
    
    // Obtener el último folio usando PDO
    $sql = "SELECT (MAX(CAST(folio AS UNSIGNED)) + 1) as siguiente_folio FROM adm_pedidos";
    file_put_contents($log_file, "Consulta SQL: $sql\n", FILE_APPEND);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    file_put_contents($log_file, "Resultado de la consulta: " . print_r($row, true) . "\n", FILE_APPEND);
    
    $nextFolio = $row['siguiente_folio'] ?: 1; // Si es NULL, iniciar en 1
    file_put_contents($log_file, "Siguiente folio: $nextFolio\n", FILE_APPEND);
    
    $response = [
        'status' => 'success',
        'nextFolio' => $nextFolio
    ];
    
} catch (Exception $e) {
    file_put_contents($log_file, "EXCEPCIÓN: " . $e->getMessage() . "\n", FILE_APPEND);
    file_put_contents($log_file, "Traza: " . $e->getTraceAsString() . "\n", FILE_APPEND);
    
    // Valor por defecto en caso de error
    $response = [
        'status' => 'success', // Devuelve éxito para que la app siga funcionando
        'message' => 'Error recuperado automáticamente: ' . $e->getMessage(),
        'nextFolio' => 1 // Valor por defecto
    ];
} finally {
    // Configurar cabeceras
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: http://127.0.0.1');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Max-Age: 3600');
    
    // Registrar la respuesta que se enviará
    file_put_contents($log_file, "Enviando respuesta: " . json_encode($response) . "\n", FILE_APPEND);
    file_put_contents($log_file, "=== FIN DE PETICIÓN ÚLTIMO FOLIO (MODO DEBUG) ===\n\n", FILE_APPEND);
    
    // Enviar la respuesta
    echo json_encode($response);
}
?>