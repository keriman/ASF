<?php
//barcode_scanner_salida.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_log', 'php_errors.log');

// Cargar el sistema de inventario
require_once 'process/InventorySystem2.php';
$inventory = new InventorySystem();

// Si hay una solicitud para guardar el lote de SALIDA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_batch_output') {
    try {
        // Recibir datos del lote
        if (!isset($_POST['batch_data']) || empty($_POST['batch_data'])) {
            throw new Exception("No hay datos para procesar");
        }
        
        $batchData = json_decode($_POST['batch_data'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error al decodificar datos del lote: " . json_last_error_msg());
        }
        
        // Preparar datos para el procesamiento del lote
        $batchItems = array();
        foreach ($batchData as $barcode => $data) {
            if (!isset($data['quantity'])) {
                continue; // Saltar elementos sin datos completos
            }
            
            // Agregar al array de elementos para procesar
            $batchItems[] = array(
                'barcode' => $barcode,
                'quantity' => (int)$data['quantity']
            );
        }
        
        // Procesar el lote completo para SALIDA
        $result = $inventory->processBatchOutput(
            $batchItems, 
            'Salida de Almacén - Lote ' . date('Y-m-d H:i:s')
        );
        
        if ($result['success']) {
            $_SESSION['swal_success'] = true;
            $_SESSION['swal_message'] = "Lote de salida procesado exitosamente: {$result['total_processed']} producto(s) actualizado(s)";
            
            if ($result['total_not_found'] > 0) {
                $_SESSION['swal_message'] .= ". {$result['total_not_found']} producto(s) no encontrado(s).";
            }
            
            if ($result['total_insufficient'] > 0) {
                $_SESSION['swal_message'] .= ". {$result['total_insufficient']} producto(s) con stock insuficiente.";
            }
        } else {
            $_SESSION['swal_error'] = true;
            $_SESSION['swal_message'] = $result['message'];
            
            // Guardar detalles de productos con stock insuficiente para mostrar
            if (!empty($result['insufficient_stock'])) {
                $_SESSION['insufficient_stock_details'] = $result['insufficient_stock'];
            }
        }
        
        echo "<script>window.location.href = 'bajaProductos.php';</script>";
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        echo "<script>window.location.href = 'bajaProductos.php';</script>";
        exit;
    }
}

// Obtener todos los productos para sincronizar con el lado del cliente
$products = $inventory->listProducts();
$productsData = array();

// Preparar datos de productos
foreach ($products as $product) {
    $barcode = isset($product['barcode']) ? $product['barcode'] : null;
    $productsData[$product['id']] = array(
        'id' => $product['id'],
        'name' => $product['name'],
        'description' => $product['description'],
        'barcode' => $barcode,
        'stock' => $product['stock'] // Agregamos el stock para verificaciones
    );
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escáner de Códigos de Barras - Salida de Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../dist/img/asf.png" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        :root {
            --primary-red: #DC3545;
            --primary-pink: #E6007E;
            --light-red: #F8D7DA;
            --dark-red: #B02A37;
            --neutral-gray: #F5F5F5;
            --text-dark: #2C3E50;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--neutral-gray);
            padding-top: 20px;
        }
        
        .card {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 8px;
            border: none;
        }
        
        .card-header {
            background-color: var(--primary-red);
            color: white;
            border-radius: 8px 8px 0 0 !important;
            padding: 15px 20px;
        }
        
        .btn-primary {
            background-color: var(--primary-red);
            border-color: var(--primary-red);
        }
        
        .btn-primary:hover {
            background-color: var(--dark-red);
            border-color: var(--dark-red);
        }
        
        .btn-danger {
            background-color: var(--primary-pink);
            border-color: var(--primary-pink);
        }
        
        .barcode-input {
            font-size: 24px;
            height: 60px;
            text-align: center;
            border: 2px solid var(--primary-red);
        }
        
        .batch-table {
            margin-top: 20px;
        }
        
        .scanner-container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .batch-summary {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
        }
        
        .quantity-btn {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: var(--light-red);
            color: var(--dark-red);
            font-weight: bold;
            cursor: pointer;
            user-select: none;
        }
        
        .quantity-input {
            width: 60px;
            text-align: center;
            margin: 0 8px;
        }
        
        .scanner-placeholder {
            height: 200px;
            border: 2px dashed #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px 0;
            font-size: 18px;
            color: #888;
        }
        
        .product-not-found {
            color: #d9534f;
            font-weight: bold;
        }
        
        .save-batch-btn {
            margin-top: 20px;
        }
        
        .stock-warning {
            color: #FF6B35;
            font-weight: bold;
        }
        
        .stock-info {
            font-size: 0.9em;
            color: #666;
        }
        
        /* Animaciones */
        .scan-animation {
            animation: scanEffect 0.5s ease-out;
        }
        
        @keyframes scanEffect {
            0% { background-color: rgba(220, 53, 69, 0.2); }
            100% { background-color: transparent; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Mensajes de alerta -->
        <?php if (isset($_SESSION['swal_success'])): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: '<?php echo $_SESSION['swal_message']; ?>',
                        icon: 'success',
                        confirmButtonText: 'Aceptar'
                    });
                });
            </script>
            <?php 
            unset($_SESSION['swal_success']);
            unset($_SESSION['swal_message']);
            ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['swal_error'])): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    let errorHtml = '<?php echo $_SESSION['swal_message']; ?>';
                    
                    <?php if (isset($_SESSION['insufficient_stock_details'])): ?>
                        errorHtml += '<br><br><strong>Productos con stock insuficiente:</strong><ul>';
                        <?php foreach ($_SESSION['insufficient_stock_details'] as $item): ?>
                            errorHtml += '<li><?php echo $item['product_name']; ?> - Solicitado: <?php echo $item['requested']; ?>, Disponible: <?php echo $item['available']; ?></li>';
                        <?php endforeach; ?>
                        errorHtml += '</ul>';
                    <?php endif; ?>
                    
                    Swal.fire({
                        title: 'Error en el procesamiento',
                        html: errorHtml,
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                });
            </script>
            <?php 
            unset($_SESSION['swal_error']);
            unset($_SESSION['swal_message']);
            unset($_SESSION['insufficient_stock_details']);
            ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Escáner de Códigos de Barras - Salida de Inventario</h4>
                    </div>
                    <div class="card-body">
                        <div class="scanner-container">
                            <div class="row">
                                <div class="col-md-8 mx-auto">
                                    <div class="form-group">
                                        <label for="barcode-input" class="form-label">Escanear código de barras o ingresar manualmente:</label>
                                        <input type="text" id="barcode-input" class="form-control barcode-input" autofocus 
                                               placeholder="Apunte el escáner o escriba el código aquí">
                                    </div>
                                    
                                    <div class="scanner-placeholder" id="scan-area">
                                        <div>Esperando escaneo... (Enfoque aquí y escanee el código)</div>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                        <button id="manual-add-btn" class="btn btn-primary">
                                            <i class="bi bi-plus-circle"></i> Agregar Manualmente
                                        </button>
                                        <button id="clear-input-btn" class="btn btn-secondary">
                                            <i class="bi bi-eraser"></i> Limpiar Campo
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="batch-summary">
                            <h5>Productos para Salida de Inventario</h5>
                            <div class="table-responsive batch-table">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Producto</th>
                                            <th>Descripción</th>
                                            <th>Stock Actual</th>
                                            <th width="120">Cantidad a Retirar</th>
                                            <th width="80">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="batch-items">
                                        <!-- Los elementos escaneados se agregarán aquí -->
                                        <tr id="empty-batch-message">
                                            <td colspan="6" class="text-center">No hay productos escaneados en este lote</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card bg-light mb-3">
                                        <div class="card-body">
                                            <h6 class="card-title">Resumen del Lote</h6>
                                            <p class="mb-1">Total de productos: <span id="total-products">0</span></p>
                                            <p>Total de unidades: <span id="total-units">0</span></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button id="clear-batch-btn" class="btn btn-warning">
                                            <i class="bi bi-trash"></i> Limpiar Lote
                                        </button>
                                        <button id="save-batch-btn" class="btn btn-danger" disabled>
                                            <i class="bi bi-arrow-down-circle"></i> Confirmar Salida de Almacén
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Formulario oculto para enviar los datos del lote -->
                            <form id="batch-form" method="POST" style="display: none;">
                                <input type="hidden" name="action" value="save_batch_output">
                                <input type="hidden" name="batch_data" id="batch-data-input">
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para selección manual de producto -->
    <div class="modal fade" id="product-select-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Seleccionar Producto para Salida</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="product-search" placeholder="Buscar producto...">
                    </div>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover">
                            <thead class="sticky-top bg-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Stock</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody id="product-list">
                                <!-- Lista de productos generada dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para asignar códigos de barras -->
    <div class="modal fade" id="assign-barcode-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Asignar Código de Barras</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>El código <strong id="scanned-barcode-display"></strong> no está asignado a ningún producto.</p>
                    <p>¿Desea asignarlo a un producto existente?</p>
                    
                    <div class="mb-3">
                        <label for="barcode-product-search" class="form-label">Buscar producto:</label>
                        <input type="text" class="form-control" id="barcode-product-search" placeholder="Escriba para buscar...">
                    </div>
                    
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-hover">
                            <thead class="sticky-top bg-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Stock</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody id="barcode-product-list">
                                <!-- Lista de productos para asignar código -->
                            </tbody>
                        </table>
                    </div>
                    
                    <input type="hidden" id="current-scanned-barcode">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sonido para el beep -->
    <audio id="beep-sound" src="InventarioASF/assets/sounds/beep.mp3" preload="auto"></audio>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Pasar datos de productos al script -->
    <script>
        // Esta variable será utilizada en el script externo
        var productDataFromServer = <?php echo json_encode($productsData); ?>;
    </script>
    
    <!-- Incluir script adaptado para salidas -->
    <script src="InventarioASF/barcode_scanner_salida_js.js"></script>
</body>
</html>