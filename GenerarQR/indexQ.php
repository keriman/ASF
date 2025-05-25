<?php
    ini_set('display_startup_errors', 1);
    ini_set('display_errors', 1);
    error_reporting(ALL);
// Incluir el archivo de conexión
require_once '../pages/conexion.php';

// Consulta para obtener todos los productos ordenados por ID
$stmt = $pdo->query("SELECT id, name, description, barcode, stock FROM products_quinagro ORDER BY id ASC");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de Códigos QR - AgroSant</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col">
                <h1 class="text-center">Generador de Códigos QR - Quinagro</h1>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="imprimir_todos_simple_q.php" class="btn btn-primary">Imprimir todos los QR (86 productos)</a>
                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#helpModal">
                        Ayuda con QR
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Modal de ayuda -->
        <div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="helpModalLabel">Ayuda con Códigos QR</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <h5>Información sobre los Códigos QR</h5>
                        <p>Esta aplicación genera códigos QR simples que contienen únicamente el código de barras del producto.</p>
                        
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">QR Simple</div>
                            <div class="card-body">
                                <p>El <strong>QR Simple</strong> contiene únicamente el código de barras del producto:</p>
                                <p>Ejemplo: <code>750273100011</code></p>
                                <p class="text-muted">Este formato es compatible con la mayoría de los lectores de códigos QR y es el recomendado para su uso.</p>
                            </div>
                        </div>
                        
                        <h5 class="mt-4">Opciones disponibles</h5>
                        <ul>
                            <li><strong>Ver QR Simple</strong>: Muestra el código QR con solo el código de barras</li>
                            <li><strong>Imprimir QR Simple</strong>: Abre una página de impresión para un solo producto</li>
                            <li><strong>Imprimir todos los QR Simples</strong>: Genera e imprime todos los códigos QR de productos con stock</li>
                        </ul>
                        
                        <h5 class="mt-4">Consejos para escanear</h5>
                        <ol>
                            <li>Asegúrese de que su dispositivo tenga suficiente luz para escanear.</li>
                            <li>Imprima los códigos en una resolución adecuada.</li>
                            <li>Mantenga la cámara estable al escanear.</li>
                            <li>Si tiene problemas, intente ajustar la distancia entre la cámara y el código QR.</li>
                        </ol>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Producto</th>
                                <th>Descripción</th>
                                <th>Código de Barras</th>
                                <th>Stock</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($producto['id']); ?></td>
                                <td><?php echo htmlspecialchars($producto['name']); ?></td>
                                <td><?php echo htmlspecialchars($producto['description']); ?></td>
                                <td><?php echo htmlspecialchars($producto['barcode']); ?></td>
                                <td><?php echo htmlspecialchars($producto['stock']); ?></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="generar_qr_simple_q.php?id=<?php echo $producto['id']; ?>" class="btn btn-primary btn-sm" target="_blank">Ver QR Simple</a>
                                        <a href="imprimir_simple_q.php?id=<?php echo $producto['id']; ?>" class="btn btn-success btn-sm" target="_blank">Imprimir QR Simple</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>