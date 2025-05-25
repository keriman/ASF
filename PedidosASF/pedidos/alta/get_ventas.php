<?php
// Mostrar todos los errores para desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurar archivo de log
$log_file = __DIR__ . '/get_ventas_debug.log';
file_put_contents($log_file, "=== NUEVA PETICIÓN: " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);

// Configurar cabeceras JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Incluir configuración de la base de datos
require_once __DIR__ . '/../../conexiones/database.php';

// Obtener parámetros
$vendedor = isset($_GET['vendedor']) ? trim($_GET['vendedor']) : '';
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$porPagina = isset($_GET['por_pagina']) ? max(1, min(intval($_GET['por_pagina']), 100)) : 10;
//                                                              Paréntesis cerrado aquí ^página

// Registrar parámetros recibidos
file_put_contents($log_file, "Parámetros recibidos:\n", FILE_APPEND);
file_put_contents($log_file, "Vendedor: $vendedor\n", FILE_APPEND);
file_put_contents($log_file, "Página: $pagina\n", FILE_APPEND);
file_put_contents($log_file, "Por página: $porPagina\n", FILE_APPEND);

// Validar parámetros obligatorios
if (empty($vendedor)) {
    $response = [
        'error' => true,
        'message' => 'Se requiere el parámetro vendedor',
        'ventas' => [],
        'paginacion' => null
    ];
    file_put_contents($log_file, "Error: vendedor vacío\n", FILE_APPEND);
    echo json_encode($response);
    exit;
}

try {
    // Conectar a la base de datos
    $conectar = mysqli_connect($host, $user, $clave, $datbase);
    
    if (!$conectar) {
        throw new Exception("Error de conexión: " . mysqli_connect_error());
    }
    
    mysqli_set_charset($conectar, 'utf8mb4');
    
    // Escapar parámetros para seguridad
    $vendedor_escaped = mysqli_real_escape_string($conectar, $vendedor);
    
    // Calcular offset
    $offset = ($pagina - 1) * $porPagina;
    
    // Consulta principal con paginación
    $sql = "SELECT SQL_CALC_FOUND_ROWS 
                folio, vendedor, cliente, destino, ruta, 
                DATE_FORMAT(fecha_salida, '%d/%m/%Y') as fecha_salida, 
                FR 
            FROM adm_pedidos 
            WHERE vendedor = '$vendedor_escaped' 
            ORDER BY FR DESC, fecha_salida DESC
            LIMIT $offset, $porPagina";
    
    file_put_contents($log_file, "Consulta SQL: $sql\n", FILE_APPEND);
    
    $result = mysqli_query($conectar, $sql);
    
    if (!$result) {
        throw new Exception("Error en consulta: " . mysqli_error($conectar));
    }
    
    // Obtener ventas
    $ventas = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $ventas[] = $row;
    }
    
    // Obtener total de registros
    $totalQuery = mysqli_query($conectar, "SELECT FOUND_ROWS() AS total");
    $totalRegistros = mysqli_fetch_assoc($totalQuery)['total'];
    $totalPaginas = ceil($totalRegistros / $porPagina);
    
    // Construir respuesta
    $response = [
        'error' => false,
        'message' => '',
        'ventas' => $ventas,
        'paginacion' => [
            'pagina_actual' => $pagina,
            'por_pagina' => $porPagina,
            'total_registros' => $totalRegistros,
            'total_paginas' => $totalPaginas
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    file_put_contents($log_file, "Excepción: " . $e->getMessage() . "\n", FILE_APPEND);
    
    $response = [
        'error' => true,
        'message' => $e->getMessage(),
        'ventas' => [],
        'paginacion' => null
    ];
    
    echo json_encode($response);
} finally {
    // Cerrar conexión si existe
    if (isset($conectar)) {
        mysqli_close($conectar);
    }
    
    file_put_contents($log_file, "=== FIN DE PETICIÓN ===\n\n", FILE_APPEND);
}
?>