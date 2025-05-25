<?php
// Incluir el archivo de conexión
require_once '../pages/conexion.php';
// Incluir la biblioteca phpqrcode
require_once '../lib/phpqrcode/qrlib.php';

// Verificar si se proporciona un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: No se proporcionó un ID de producto válido.");
}

$id = $_GET['id'];

// Obtener información del producto
try {
    $stmt = $pdo->prepare("SELECT id, name, description, barcode, stock FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$producto) {
        die("Error: Producto no encontrado.");
    }
} catch (PDOException $e) {
    die("Error al consultar la base de datos: " . $e->getMessage());
}

// Crear directorio para almacenar códigos QR si no existe
$qr_directory = '../temp/qrcodes/';
if (!file_exists($qr_directory)) {
    mkdir($qr_directory, 0777, true);
}

// Crear el nombre del archivo QR
$qr_filename = $qr_directory . 'qr_' . $producto['id'] . '.png';

// Datos para el código QR (puedes personalizar esta estructura según tus necesidades)
$qr_data = json_encode([
    'id' => $producto['id'],
    'name' => $producto['name'],
    'barcode' => $producto['barcode']
]);

// Generar código QR
QRcode::png($qr_data, $qr_filename, QR_ECLEVEL_L, 10, 2);

// Obtener la ruta relativa para el HTML
$qr_html_path = $qr_filename;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imprimir QR - <?php echo htmlspecialchars($producto['name']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .print-container {
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
            padding: 10px;
            border: 1px dashed #ccc;
            text-align: center;
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
            .print-container {
                border: none;
            }
        }
    </style>
</head>
<body>
    <div class="print-instructions no-print">
        <h2>Instrucciones de impresión</h2>
        <p>Haga clic en el botón "Imprimir" para imprimir el código QR. En la ventana de impresión, asegúrese de configurar la impresión sin márgenes para obtener mejores resultados.</p>
        <button onclick="window.print();" class="print-button">Imprimir</button>
        <a href="index.php" class="back-button">Volver a la lista</a>
    </div>
    
    <div class="print-container">
        <img src="<?php echo $qr_html_path; ?>" alt="Código QR" class="qr-code">
        <div class="product-info">
            <strong><?php echo htmlspecialchars($producto['name']); ?></strong><br>
            <?php echo htmlspecialchars($producto['description']); ?><br>
            <small>Código: <?php echo htmlspecialchars($producto['barcode']); ?></small>
        </div>
    </div>
    
    <script>
        // Auto imprimir después de cargar la página (opcional, comenta si no lo deseas)
        // window.onload = function() {
        //     setTimeout(function() {
        //         window.print();
        //     }, 1000);
        // };
    </script>
</body>
</html>
