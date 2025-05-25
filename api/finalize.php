<?php
require_once 'config.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $session = json_decode(file_get_contents('php://input'), true);
    
    if (!$session || !isset($session['productGroups'])) {
        throw new Exception('Datos de sesión inválidos');
    }

    $db = $inventory->getConnection();
    $db->beginTransaction();

    try {
        foreach ($session['productGroups'] as $group) {
            foreach ($group['products'] as $product) {
                $inventory->updateStock(
                    $product['barcode'],
                    1,
                    'add',
                    "Sesión: {$session['id']}"
                );
            }
        }
        
        $db->commit();
        sendResponse(true, true, 'Inventario finalizado correctamente');
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
} catch (Exception $e) {
    handleError($e);
}