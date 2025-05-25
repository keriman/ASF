<?php
// products/get_products.php

// Habilitar reporte de errores para depuración
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Preparar respuesta
$response = array(
    'error' => false,
    'message' => '',
    'products' => array()
);

try {
    // Incluir archivo de conexión
    require_once '../../pages/conexion.php';
    
    // Tu archivo conexion.php ya crea la conexión en la variable $pdo
    // así que podemos usarla directamente

    // Consultar todos los productos
    $query = "SELECT id, name, description, stock, created_at, updated_at FROM products_quinagro ORDER BY description, name";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    // Obtener resultados
    $response['products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($response['products']) == 0) {
        $response['message'] = "No se encontraron productos";
    } else {
        $response['message'] = "Se encontraron " . count($response['products']) . " productos";
    }
} catch (PDOException $e) {
    $response['error'] = true;
    $response['message'] = "Error de base de datos: " . $e->getMessage();
} catch (Exception $e) {
    $response['error'] = true;
    $response['message'] = "Error: " . $e->getMessage();
}

// Devolver respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode($response);
?>