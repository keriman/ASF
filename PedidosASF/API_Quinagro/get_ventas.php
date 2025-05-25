<?php
// Mostrar todos los errores para desarrollo

// Configurar archivo de log
$log_file = __DIR__ . '/get_ventas_debug.log';
file_put_contents($log_file, "=== NUEVA PETICIÓN: " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);

// Configurar cabeceras JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Incluir configuración de la base de datos
require_once __DIR__ . '/../../pages/conexion.php';

// Registrar variables de conexión disponibles
file_put_contents($log_file, "Variables de conexión disponibles:\n", FILE_APPEND);
file_put_contents($log_file, "host: " . (isset($host) ? $host : 'no definido') . "\n", FILE_APPEND);
file_put_contents($log_file, "username: " . (isset($username) ? $username : 'no definido') . "\n", FILE_APPEND);
file_put_contents($log_file, "dbname: " . (isset($dbname) ? $dbname : 'no definido') . "\n", FILE_APPEND);
file_put_contents($log_file, "¿Existe pdo? " . (isset($pdo) ? 'sí' : 'no') . "\n", FILE_APPEND);

// Obtener parámetros
$vendedor = isset($_GET['vendedor']) ? trim($_GET['vendedor']) : '';
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$porPagina = isset($_GET['por_pagina']) ? max(1, min(intval($_GET['por_pagina']), 100)) : 10;

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
    // Usar PDO que ya está definido en conexion.php
    if (isset($pdo)) {
        file_put_contents($log_file, "Usando conexión PDO existente\n", FILE_APPEND);
        
        // Calcular offset
        $offset = ($pagina - 1) * $porPagina;
        
        // Consulta principal con paginación
        $sql = "SELECT 
                    folio, vendedor, cliente, destino, ruta, 
                    DATE_FORMAT(fecha_salida, '%d/%m/%Y') as fecha_salida, 
                    FR 
                FROM adm_pedidos 
                WHERE vendedor = :vendedor 
                ORDER BY FR DESC, fecha_salida DESC
                LIMIT :offset, :porPagina";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':vendedor', $vendedor, PDO::PARAM_STR);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':porPagina', $porPagina, PDO::PARAM_INT);
        $stmt->execute();
        
        // Obtener ventas
        $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener total de registros
        $totalQuery = $pdo->query("SELECT COUNT(*) as total FROM adm_pedidos WHERE vendedor = " . $pdo->quote($vendedor));
        $totalRegistros = $totalQuery->fetch(PDO::FETCH_ASSOC)['total'];
        $totalPaginas = ceil($totalRegistros / $porPagina);
        
    } else {
        // Método alternativo si no hay PDO: usar mysqli con nombres correctos
        file_put_contents($log_file, "Usando conexión mysqli alternativa\n", FILE_APPEND);
        
        // Asignar los nombres de variables correctos
        $user_var = isset($username) ? $username : 'root';
        $clave_var = isset($password) ? $password : '';
        $database_var = isset($dbname) ? $dbname : 'agrosant_pedidos';
        $host_var = isset($host) ? $host : 'localhost';
        
        file_put_contents($log_file, "Variables usadas para conexión mysqli: host=$host_var, user=$user_var, db=$database_var\n", FILE_APPEND);
        
        // Conectar a la base de datos
        $conectar = mysqli_connect($host_var, $user_var, $clave_var, $database_var);
        
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
    }
    
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