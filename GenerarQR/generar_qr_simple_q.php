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
    $stmt = $pdo->prepare("SELECT id, name, description, barcode, stock FROM products_quinagro WHERE id = ?");
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
$qr_filename = $qr_directory . 'qr_simple_' . $producto['id'] . '.png';

// Usar solo el código de barras como dato para el QR
$qr_data = $producto['barcode'];

// Generar código QR con modo alfanumérico y nivel alto de corrección de errores
QRcode::png($qr_data, $qr_filename, QR_ECLEVEL_H, 8, 2);

// Mostrar la información del producto y el código QR
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código QR Simple - <?php echo htmlspecialchars($producto['name']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col">
                <h1 class="text-center">Código QR Simple</h1>
                <p class="text-center text-muted">Este QR contiene únicamente el código de barras para mayor compatibilidad</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="text-center mb-0">
                            <?php echo htmlspecialchars($producto['name']); ?>
                        </h4>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <img src="<?php echo $qr_filename; ?>" alt="Código QR" class="img-fluid qr-code">
                        </div>
                        <p><strong>ID:</strong> <?php echo htmlspecialchars($producto['id']); ?></p>
                        <p><strong>Código de Barras:</strong> <?php echo htmlspecialchars($producto['barcode']); ?></p>
                        <p class="text-muted small">Este QR solo contiene el código: <?php echo htmlspecialchars($producto['barcode']); ?></p>
                    </div>
                    <div class="card-footer text-center">
                        <a href="imprimir_simple_q.php?id=<?php echo $producto['id']; ?>" class="btn btn-success" target="_blank">Imprimir QR Simple</a>
                        <a href="../generadorq.php" class="btn btn-secondary">Volver a la lista</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>