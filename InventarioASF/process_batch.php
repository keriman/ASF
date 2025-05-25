<?php
// process_batch.php
// Script para procesar lotes completos y actualizar el inventario

// Configuración de headers CORS y JSON
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_log', 'batch_error.log');

// Función para registrar mensajes
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] $message\n", 3, __DIR__ . '/batch_error.log');
}

logMessage("Iniciando procesamiento de lote");

try {
    // Cargar el sistema de inventario
    require_once 'process/InventorySystem2.php';
    $inventory = new InventorySystem();
    
    // Verificar método de la petición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido: ' . $_SERVER['REQUEST_METHOD']);
    }
    
    // Verificar si es una solicitud CORS preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
    
    // Obtener datos de la petición
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error decodificando JSON: ' . json_last_error_msg());
    }
    
    // Validar datos requeridos
    if (!isset($input['batch']) || !is_array($input['batch']) || empty($input['batch'])) {
        throw new Exception('Datos incompletos: Se requiere un array de lote no vacío');
    }
    
    $batch = $input['batch'];
    $batchNotes = isset($input['notes']) ? $input['notes'] : 'Ingreso por lote';
    
    logMessage("Procesando lote con " . count($batch) . " elementos");
    
    // Procesar el lote usando el método de la clase InventorySystem
    try {
        $result = $inventory->processBatch($batch, $batchNotes);
        
        $processedItems = $result['processed'];
        $notFoundItems = $result['not_found'];
        $errors = array();
    
        // Si hay productos no encontrados, registrar en el log
        foreach ($notFoundItems as $item) {
            logMessage("Producto con código de barras '{$item['barcode']}' no encontrado");
        }
        
        logMessage("Lote procesado exitosamente. {$result['total_processed']} productos procesados.");
        
    } catch (Exception $e) {
        logMessage("Error procesando lote: " . $e->getMessage());
        throw $e;
    }
    
    // Enviar respuesta exitosa
    echo json_encode(array(
        'success' => true,
        'message' => 'Lote procesado exitosamente',
        'data' => array(
            'processed' => $processedItems,
            'not_found' => $notFoundItems,
            'total_processed' => count($processedItems),
            'total_not_found' => count($notFoundItems)
        )
    ));
    
} catch (Exception $e) {
    logMessage("ERROR CRÍTICO: " . $e->getMessage());
    
    // Enviar respuesta de error
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => $e->getMessage(),
        'errors' => isset($errors) ? $errors : array()
    ));
}