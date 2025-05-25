<?php
// generate_barcode.php
header('Content-Type: application/json');

// Obtener ID de producto
$productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if ($productId <= 0) {
    echo json_encode(array('success' => false, 'message' => 'ID de producto no válido'));
    exit;
}

// Generar código Code 128
$barcode = 'ASF-' . str_pad($productId, 5, '0', STR_PAD_LEFT);

echo json_encode(array(
    'success' => true,
    'barcode' => $barcode
));