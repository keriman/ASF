<?php
// Incluir el archivo de conexión
require_once '../pages/conexion.php';
// Incluir la biblioteca phpqrcode
require_once '../lib/phpqrcode/qrlib.php';

// Consultar todos los productos con stock mayor a 0
$stmt = $pdo->query("SELECT id, name, description, barcode, stock FROM products WHERE stock > 0 ORDER BY name ASC");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crear directorio para almacenar códigos QR si no existe
$qr_directory = '../temp/qrcodes/';
if (!file_exists($qr_directory)) {
    mkdir($qr_directory, 0777, true);
}

// Generar QR para cada producto
$qr_paths = [];
foreach ($productos as $producto) {
    // Crear el nombre del archivo QR
    $qr_filename = $qr_directory . 'qr_' . $producto['id'] . '.png';
    
    // Datos para el código QR en formato de texto plano más compatible
    $qr_data = "ID:" . $producto['id'] . "|PROD:" . $producto['name'] . "|COD:" . $producto['barcode'];
    
    // Generar código QR con mayor nivel de corrección de errores y mejor tamaño
    QRcode::png($qr_data, $qr_filename, QR_ECLEVEL_M, 8, 2);
    
    // Guardar la ruta del QR junto con los datos del producto
    $producto['qr_path'] = $qr_filename;
    $qr_paths[] = $producto;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imprimir Todos los Códigos QR - AgroSant</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .print-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }
        .qr-card {
            width: 300px;
            padding: 10px;
            border: 1px dashed #ccc;
            text-align: center;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .qr-code {
            width: 200px;
            height: 200px;
            margin: 0 auto;
        }
        .product-info {
            margin-top: 15px;
            font-size: 14px;
            line-height: 1.4;
        }
        .print-instructions {
            margin: 20px auto;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            max-width: 600px;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            .qr-card {
                border: none;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="print-instructions no-print">
        <h2>Imprimir todos los Códigos QR</h2>
        <p>A continuación se muestran los códigos QR para todos los productos con stock disponible. Haga clic en el botón "Imprimir" para imprimirlos todos.</p>
        <p><strong>Total de productos:</strong> <?php echo count($qr_paths); ?></p>
        <button onclick="window.print();" class="print-button">Imprimir Todos</button>
        <a href="index.php" class="back-button">Volver a la lista</a>
    </div>
    
    <div class="print-grid">
        <?php foreach ($qr_paths as $producto): ?>
        <div class="qr-card">
            <img src="<?php echo $producto['qr_path']; ?>" alt="Código QR" class="qr-code">
            <div class="product-info">
                <strong><?php echo htmlspecialchars($producto['name']); ?></strong><br>
                <?php echo htmlspecialchars($producto['description']); ?><br>
                <small>Código: <?php echo htmlspecialchars($producto['barcode']); ?></small><br>
                <small>Stock: <?php echo htmlspecialchars($producto['stock']); ?></small>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>