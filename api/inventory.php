<?php
require_once 'config.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['productId']) || !isset($data['quantity']) || !isset($data['type'])) {
        throw new Exception('Datos incompletos');
    }

    $result = $inventory->updateStock(
        $data['productId'],
        $data['quantity'],
        $data['type'],
        $data['notes'] ?? ''
    );

    sendResponse($result, true, 'Stock actualizado correctamente');
} catch (Exception $e) {
    handleError($e);
}