<?php
//update_inventory.php
// Forzar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] $message\n", 3, __DIR__ . '/error.log');
}

logMessage("Script iniciado");

try {
    // Headers CORS primero
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Content-Type: application/json');

    logMessage("Headers CORS establecidos");

    // Preflight request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    logMessage("Método de la petición: " . $_SERVER['REQUEST_METHOD']);

    // Solo permitir POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido: ' . $_SERVER['REQUEST_METHOD']);
    }

    // Obtener y validar input
    $rawInput = file_get_contents('php://input');
    logMessage("Input recibido: " . $rawInput);

    $input = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error decodificando JSON: ' . json_last_error_msg());
    }

    if (!isset($input['producto']) || !isset($input['cantidad'])) {
        throw new Exception('Datos incompletos en el input');
    }

    logMessage("Input validado correctamente");

    // Cargar sistema de inventario
    $inventoryFile = __DIR__ . '/InventorySystem2.php';
    logMessage("Intentando cargar: " . $inventoryFile);

    require_once $inventoryFile;
    logMessage("Sistema de inventario cargado");

    // Crear instancia y obtener productos
    $inventory = new InventorySystem();
    $products = $inventory->listProducts();
    logMessage("Productos obtenidos: " . count($products));

    // Buscar producto
    $productId = null;
    foreach ($products as $product) {
        if (strtoupper($product['name']) === strtoupper($input['producto'])) {
            $productId = $product['id'];
            logMessage("Producto encontrado - ID: " . $productId . ", Nombre: " . $product['name']);
            break;
        }
    }

    if (!$productId) {
        throw new Exception('Producto no encontrado: ' . $input['producto']);
    }

    // Actualizar stock
    logMessage("Actualizando stock - ID: $productId, Cantidad: {$input['cantidad']}");
    $inventory->updateStock(
        $productId,
        $input['cantidad'],
        'add',
        'Agregado Produccion'
    );
    logMessage("Stock actualizado exitosamente");

    // Enviar respuesta
    $response = [
        'success' => true,
        'message' => 'Inventario actualizado correctamente',
        'productId' => $productId,
        'cantidad' => $input['cantidad']
    ];
    logMessage("Enviando respuesta: " . json_encode($response));
    echo json_encode($response);

} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
    
    $errorResponse = [
        'success' => false,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
    
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode($errorResponse);
}