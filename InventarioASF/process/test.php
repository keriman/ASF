<?php
// Habilitar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Información del sistema
$info = [
    'script_path' => __FILE__,
    'directory' => __DIR__,
    'document_root' => $_SERVER['DOCUMENT_ROOT'],
    'user' => get_current_user(),
    'php_version' => PHP_VERSION,
    'write_test' => false,
    'inventory_file' => false
];

// Probar escritura
$testFile = __DIR__ . '/test_write.txt';
try {
    file_put_contents($testFile, 'Test write');
    $info['write_test'] = true;
    unlink($testFile);
} catch (Exception $e) {
    $info['write_error'] = $e->getMessage();
}

// Verificar archivo InventorySystem2
$inventoryFile = __DIR__ . '/../process/InventorySystem2.php';
$info['inventory_path'] = $inventoryFile;
$info['inventory_exists'] = file_exists($inventoryFile);

// Verificar directorio process
$processDir = __DIR__ . '/../process';
$info['process_dir'] = $processDir;
$info['process_exists'] = is_dir($processDir);
$info['process_readable'] = is_readable($processDir);
$info['process_writable'] = is_writable($processDir);

// Headers
header('Content-Type: application/json');
echo json_encode($info, JSON_PRETTY_PRINT);