<?php
// assign_barcode.php
// Script para asignar o actualizar códigos de barras a productos

// Configuración de headers CORS y JSON
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_log', 'barcode_error.log');

// Función para registrar mensajes
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] $message\n", 3, __DIR__ . '/barcode_error.log');
}

logMessage("Iniciando proceso de asignación de código de barras");

try {
    // Verificar método de la petición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido: ' . $_SERVER['REQUEST_METHOD']);
    }
    
    // Obtener datos de la petición
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error decodificando JSON: ' . json_last_error_msg());
    }
    
    // Validar datos requeridos
    if (!isset($input['product_id']) || !isset($input['barcode'])) {
        throw new Exception('Datos incompletos: Se requieren product_id y barcode');
    }
    
    $productId = $input['product_id'];
    $barcode = $input['barcode'];
    
    logMessage("Asignando código $barcode al producto #$productId");
    
    // Cargar configuración de la base de datos
    $host = 'localhost';
    $dbname = 'agrosant_pedidos';
    $username = 'root';
    $password = '';
    
    // Crear conexión a la base de datos
    $db = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        )
    );
    
    // Verificar si existe el producto
    $stmt = $db->prepare('SELECT * FROM products WHERE id = :id');
    $stmt->execute(array(':id' => $productId));
    $product = $stmt->fetch();
    
    if (!$product) {
        throw new Exception("Producto con ID $productId no encontrado");
    }
    
    // Verificar si el código ya está asignado a otro producto
    $stmt = $db->prepare('SELECT * FROM products WHERE barcode = :barcode AND id != :id');
    $stmt->execute(array(
        ':barcode' => $barcode,
        ':id' => $productId
    ));
    $existingBarcode = $stmt->fetch();
    
    if ($existingBarcode) {
        throw new Exception("El código de barras ya está asignado al producto '" . $existingBarcode['name'] . "'");
    }
    
    // Actualizar el código de barras del producto
    $stmt = $db->prepare('UPDATE products SET barcode = :barcode, updated_at = NOW() WHERE id = :id');
    $result = $stmt->execute(array(
        ':barcode' => $barcode,
        ':id' => $productId
    ));
    
    if (!$result) {
        throw new Exception("Error al actualizar el código de barras");
    }
    
    logMessage("Código de barras asignado correctamente");
    
    // Enviar respuesta exitosa
    echo json_encode(array(
        'success' => true,
        'message' => 'Código de barras asignado correctamente',
        'data' => array(
            'product_id' => $productId,
            'product_name' => $product['name'],
            'barcode' => $barcode
        )
    ));
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    
    // Enviar respuesta de error
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => $e->getMessage()
    ));

