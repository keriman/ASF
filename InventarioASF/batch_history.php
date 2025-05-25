<?php
//batch_history.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_log', 'php_errors.log');

try {
    require_once 'process/InventorySystem2.php';
    $inventory = new InventorySystem();
    
    // Obtener datos de lotes
    $batchHistory = $inventory->getBatchHistory();
} catch (Exception $e) {
    die('Error del sistema: ' . $e->getMessage());
}

// Filtrar por fecha
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

if (!empty($startDate) && !empty($endDate)) {
    $filteredHistory = array_filter($batchHistory, function($batch) use ($startDate, $endDate) {
        $batchDate = date('Y-m-d', strtotime($batch['timestamp']));
        return $batchDate >= $startDate && $batchDate <= $endDate;
    });
} else {
    $filteredHistory = $batchHistory;
}

// Ordenar por fecha (más reciente primero)
usort($filteredHistory, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Lotes - Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="../dist/img/asf.png" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        :root {
            --primary-green: #008F39;
            --primary-pink: #E6007E;
            --light-green: #E8F5E9;
            --dark-green: #005C24;
            --neutral-gray: #F5F5F5;
            --text-dark: #2C3E50;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--neutral-gray);
            padding-top: 20px;
            color: var(--text-dark);
        }
        
        .card {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: var(--primary-green);
            color: white;
            border-radius: 8px 8px 0 0 !important;
            padding: 15px 20px;
        }
        
        .btn-primary {
            background-color: var(--primary-green);
            border-color: var(--primary-green);
        }
        
        .btn-primary:hover {
            background-color: var(--dark-green);
            border-color: var(--dark-green);
        }
        
        .batch-card {
            transition: transform 0.2s;
            border-left: 5px solid var(--primary-green);
        }
        
        .batch-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        
        .batch-timestamp {
            color: var(--dark-green);
            font-weight: 500;
        }
        
        .batch-items {
            max-height: 300px;
            overflow-y: auto;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
            margin: 10px 0;
            padding: 10px 0;
        }
        
        .batch-summary {
            display: flex;
            justify-content: space-between;
            font-weight: 500;
        }
        
        .print-btn {
            float: right;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            .container {
                width: 100%;
                max-width: 100%;
            }
            
            .card {
                border: 1px solid #ddd;
                box-shadow: none;
            }
            
            .batch-card {
                page-break-inside: avoid;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Mensajes de alerta -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show no-print">
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show no-print">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4 no-print">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Historial de Lotes</h4>
                        <div>
                            <a href="barcode_scanner.php" class="btn btn-light btn-sm me-2">
                                <i class="bi bi-upc-scan"></i> Escáner de Códigos
                            </a>
                            <a href="manage_barcodes.php" class="btn btn-light btn-sm me-2">
                                <i class="bi bi-tag"></i> Gestionar Códigos
                            </a>
                            <a href="index.php" class="btn btn-light btn-sm">
                                <i class="bi bi-arrow-left"></i> Volver al Inventario
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filtros de fecha -->
                        <form action="" method="GET" class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="start_date" class="form-label">Fecha Inicio</label>
                                <input type="date" id="start_date" name="start_date" class="form-control" 
                                       value="<?php echo htmlspecialchars($startDate); ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="end_date" class="form-label">Fecha Fin</label>
                                <input type="date" id="end_date" name="end_date" class="form-control" 
                                       value="<?php echo htmlspecialchars($endDate); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label d-block">&nbsp;</label>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-filter"></i> Filtrar
                                </button>
                                <a href="batch_history.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Limpiar
                                </a>
                                <button type="button" id="print-btn" class="btn btn-info ms-2 float-end">
                                    <i class="bi bi-printer"></i> Imprimir
                                </button>
                            </div>
                        </form>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <h5>Resultados: <?php echo count($filteredHistory); ?> lotes</h5>
                        </div>
                    </div>
                </div>
                
                <!-- Lotes -->
                <?php if (empty($filteredHistory)): ?>
                    <div class="alert alert-info">
                        No se encontraron lotes en el período seleccionado.
                    </div>
                <?php else: ?>
                    <?php foreach ($filteredHistory as $batch): ?>
                        <div class="card batch-card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="batch-timestamp">
                                        <i class="bi bi-clock"></i> 
                                        <?php echo date('d/m/Y H:i:s', strtotime($batch['timestamp'])); ?>
                                    </h5>
                                    <span class="badge bg-primary">Lote #<?php echo $batch['batch_id']; ?></span>
                                </div>
                                
                                <p class="mb-2">
                                    <strong>Nota:</strong> <?php echo htmlspecialchars($batch['notes']); ?>
                                </p>
                                
                                <div class="batch-items">
                                    <table class="table table-striped table-sm">
                                        <thead>
                                            <tr>
                                                <th>Producto</th>
                                                <th>Código</th>
                                                <th>Descripción</th>
                                                <th class="text-end">Cantidad</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($batch['items'] as $item): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                    <td>
                                                        <span class="badge bg-secondary">
                                                            <?php echo htmlspecialchars($item['barcode'] ?? 'N/A'); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                                                    <td class="text-end"><?php echo $item['quantity']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="batch-summary mt-2">
                                    <span>Productos: <?php echo count($batch['items']); ?></span>
                                    <span>Total unidades: <?php echo array_sum(array_column($batch['items'], 'quantity')); ?></span>
                                    <span>Operador: <?php echo htmlspecialchars($batch['user'] ?? 'Sistema'); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Botón de imprimir
        document.getElementById('print-btn').addEventListener('click', function() {
            window.print();
        });
        
        // Validación de fechas
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        
        endDateInput.addEventListener('change', function() {
            if (startDateInput.value && this.value) {
                if (this.value < startDateInput.value) {
                    alert('La fecha de fin no puede ser anterior a la fecha de inicio');
                    this.value = startDateInput.value;
                }
            }
        });
        
        startDateInput.addEventListener('change', function() {
            if (endDateInput.value && this.value) {
                if (this.value > endDateInput.value) {
                    endDateInput.value = this.value;
                }
            }
        });
    });
    </script>
</body>
</html>