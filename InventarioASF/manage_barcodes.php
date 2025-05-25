<?php
//manage_barcodes.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_log', 'php_errors.log');

try {
    require_once 'process/InventorySystem2.php';
    $inventory = new InventorySystem();
    $products = $inventory->listProducts();
} catch (Exception $e) {
    die('Error del sistema: ' . $e->getMessage());
}

// Procesar actualización de código de barras
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_barcode') {
    try {
        $productId = $_POST['product_id'];
        $barcode = trim($_POST['barcode']);
        
        // Si está vacío, establecer como NULL
        if (empty($barcode)) {
            $barcode = null;
        }
        
        $inventory->assignBarcode($productId, $barcode);
        $_SESSION['message'] = 'Código de barras actualizado correctamente';
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    // Redireccionar para evitar reenvío del formulario
    header('Location: manage_barcodes.php');
    exit;
}

// Filtrar productos
$filterCategory = isset($_GET['category']) ? $_GET['category'] : 'all';
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Definir los productos por categoría
$acidificantesProducts = array(
    'Acid tec',
    'Agroacid 19L',
    'Agroacid 1L',
    'Agroacid 250 ml',
    'Agroacid 5L',
    'Balance 19L',
    'Balance PH 1L',
    'Balance PH 5L'
);

$adherentesProducts = array(
    'Adherex',
    'Adhetec 1 lt',
    'Adheval 19L',
    'Adheval 1L',
    'Adheval 5L',
    'Agro ADH 19L',
    'Agro ADH 1L',
    'Agro ADH 20 lt',
    'Agro ADH 250ml',
    'Agro ADH 5L',
    'Agro ADHR',
    'Tecnoadher',
    'Tecnoadher 19 lt',
    'tecnoadher 5 lt'
);

$enraizadoresProducts = array(
    '13/11/1933',
    '10-10-35 (20L)',
    'oct-35',
    '13-43-13',
    '8-24-00 1L',
    '8-24-00 20L',
    '8-24-00 5L',
    '8-24-8 (20L)',
    '8-24-8 1L',
    '8-24-8 5L',
    'Montze',
    'N-40',
    'N-44',
    'Koren'
);

$especializadosProducts = array(
    'Promotor',
    'Gramineas 1kg 1ra etapa',
    'Gramineas 1pza 1ra etapa',
    'Gramineas 1pza 2da etapa',
    'Gramineas paq 1ra etapa',
    'Gramineas paq 2da etapa'
);

$microsProducts = array(
    'CA+B',
    'Cab Zn-tec',
    'INIV',
    'Kurt 1L',
    'Kurt 20 lt',
    'Kurt 5L'
);

$npkProducts = array(
    '00-50 10 kg',
    '00-50-CA',
    'oct-60',
    '20-30-10',
    'Aguacate 1 kg',
    'Hass',
    'Llenado',
    'T-20',
    'Chayote',
    'Crecifrut',
    'DAP K',
    'Durazno 1 kg',
    'Guayaba 1 kg',
    'Nitromax',
    'Nutri HASS',
    'Nutrival',
    'Tecno 20-20',
    'Tecno balance 20-20-20',
    'Tecno urea 1 kg',
    'tecnogreen 1 kg',
    'tecnogrow 20 lt',
    'tecnogrow 5 lt',
    'Tenco 20-30-10',
    'Zarzamora 1 kg'
);

$organicosProducts = array(
    'Fast 15kg',
    'Fast 200gr',
    'Fast 750gr',
    'Fulvik',
    'Fulvik 750gr',
    'Humik 15 kg',
    'Humik 450 gr',
    'Volvox 15kg',
    'Volvox 1kg',
    'Aminomax',
    'Stimulation'
);

$resinasProducts = array(
    'Agro-cinnam',
    'Agroallium'
);

// Función para agrupar productos
function groupProducts($products, $acidificantesProducts, $adherentesProducts, $enraizadoresProducts, 
                    $especializadosProducts, $microsProducts, $npkProducts, $organicosProducts, $resinasProducts) {
    $grouped = array(
        'Acidificantes' => array(),
        'Adherentes' => array(),
        'Enraizadores' => array(),
        'Especializados' => array(),
        'Micros' => array(),
        'NPK' => array(),
        'Orgánicos' => array(),
        'Resinas' => array(),
        'Otros' => array()
    );

    foreach ($products as $product) {
        if (in_array($product['name'], $acidificantesProducts)) {
            $grouped['Acidificantes'][] = $product;
        } elseif (in_array($product['name'], $adherentesProducts)) {
            $grouped['Adherentes'][] = $product;
        } elseif (in_array($product['name'], $enraizadoresProducts)) {
            $grouped['Enraizadores'][] = $product;
        } elseif (in_array($product['name'], $especializadosProducts)) {
            $grouped['Especializados'][] = $product;
        } elseif (in_array($product['name'], $microsProducts)) {
            $grouped['Micros'][] = $product;
        } elseif (in_array($product['name'], $npkProducts)) {
            $grouped['NPK'][] = $product;
        } elseif (in_array($product['name'], $organicosProducts)) {
            $grouped['Orgánicos'][] = $product;
        } elseif (in_array($product['name'], $resinasProducts)) {
            $grouped['Resinas'][] = $product;
        } else {
            $grouped['Otros'][] = $product;
        }
    }

    return $grouped;
}

// Filtrar productos por categoría y búsqueda
$filteredProducts = $products;

// Aplicar filtro de categoría
if ($filterCategory !== 'all') {
    $filteredProducts = array_filter($products, function($product) use ($filterCategory, 
        $acidificantesProducts, $adherentesProducts, $enraizadoresProducts, 
        $especializadosProducts, $microsProducts, $npkProducts, $organicosProducts, $resinasProducts) {
        
        switch ($filterCategory) {
            case 'acidificantes':
                return in_array($product['name'], $acidificantesProducts);
            case 'adherentes':
                return in_array($product['name'], $adherentesProducts);
            case 'enraizadores':
                return in_array($product['name'], $enraizadoresProducts);
            case 'especializados':
                return in_array($product['name'], $especializadosProducts);
            case 'micros':
                return in_array($product['name'], $microsProducts);
            case 'npk':
                return in_array($product['name'], $npkProducts);
            case 'organicos':
                return in_array($product['name'], $organicosProducts);
            case 'resinas':
                return in_array($product['name'], $resinasProducts);
            case 'sin_codigo':
                return empty($product['barcode']);
            case 'con_codigo':
                return !empty($product['barcode']);
            default:
                return true;
        }
    });
}

// Aplicar búsqueda
if (!empty($searchTerm)) {
    $searchTerm = strtolower($searchTerm);
    $filteredProducts = array_filter($filteredProducts, function($product) use ($searchTerm) {
        return strpos(strtolower($product['name']), $searchTerm) !== false ||
               strpos(strtolower($product['description']), $searchTerm) !== false ||
               (isset($product['barcode']) && strpos(strtolower($product['barcode']), $searchTerm) !== false);
    });
}

$groupedProducts = groupProducts($products, $acidificantesProducts, $adherentesProducts, $enraizadoresProducts, 
                              $especializadosProducts, $microsProducts, $npkProducts, $organicosProducts, $resinasProducts);

// Títulos de las categorías
$groupTitles = array(
    'Acidificantes' => 'Acidificantes',
    'Adherentes' => 'Adherentes',
    'Enraizadores' => 'Enraizadores',
    'Especializados' => 'Especializados',
    'Micros' => 'Micronutrientes',
    'NPK' => 'N-P-K',
    'Orgánicos' => 'Orgánicos',
    'Resinas' => 'Resinas',
    'Otros' => 'Otros Productos'
);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Códigos de Barras - Inventario</title>
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
        
        .barcode-input {
            font-family: monospace;
            letter-spacing: 0.5px;
        }
        
        .has-barcode {
            background-color: var(--light-green);
        }
        
        .product-card {
            transition: transform 0.2s;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        
        .category-title {
            color: var(--dark-green);
            border-bottom: 2px solid var(--primary-green);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .badge-barcode {
            background-color: var(--primary-green);
            color: white;
        }
        
        .badge-no-barcode {
            background-color: var(--primary-pink);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Mensajes de alerta -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
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
                        <h4 class="mb-0">Gestión de Códigos de Barras</h4>
                        <div>
                            <a href="barcode_scanner.php" class="btn btn-light btn-sm me-2">
                                <i class="bi bi-upc-scan"></i> Escáner de Códigos
                            </a>
                            <a href="index.php" class="btn btn-light btn-sm">
                                <i class="bi bi-arrow-left"></i> Volver al Inventario
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filtros -->
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <form action="" method="POST" class="mt-3">
                                    <input type="hidden" name="action" value="update_barcode">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <div class="input-group">
                                        <input type="text" name="barcode" class="form-control barcode-input" 
                                               placeholder="Código de barras" 
                                               value="<?php echo htmlspecialchars(isset($product['barcode']) ? $product['barcode'] : ''); ?>">
                                        <!-- Añadir este botón -->
                                        <button type="button" class="btn btn-warning generate-barcode-btn" data-product-id="<?php echo $product['id']; ?>">
                                            <i class="bi bi-magic"></i>
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check2"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="btn-group" role="group">
                                    <a href="?category=sin_codigo" class="btn <?php echo $filterCategory === 'sin_codigo' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                        Sin código 
                                        <span class="badge bg-light text-dark ms-1">
                                            <?php 
                                            $sinCodigo = 0;
                                            foreach ($products as $p) {
                                                if (empty($p['barcode'])) {
                                                    $sinCodigo++;
                                                }
                                            }
                                            echo $sinCodigo;
                                            ?>
                                        </span>
                                    </a>
                                    <a href="?category=con_codigo" class="btn <?php echo $filterCategory === 'con_codigo' ? 'btn-success' : 'btn-outline-success'; ?>">
                                        Con código 
                                        <span class="badge bg-light text-dark ms-1">
                                            <?php 
                                            $conCodigo = 0;
                                            foreach ($products as $p) {
                                                if (!empty($p['barcode'])) {
                                                    $conCodigo++;
                                                }
                                            }
                                            echo $conCodigo;
                                            ?>
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Lista de productos -->
                        <div class="row">
                            <?php if (empty($filteredProducts)): ?>
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        No se encontraron productos que coincidan con los criterios de búsqueda.
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($filteredProducts as $product): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card product-card h-100 <?php echo !empty($product['barcode']) ? 'has-barcode' : ''; ?>">
                                            <div class="card-body">
                                                <h5 class="card-title">
                                                    <?php echo htmlspecialchars($product['name']); ?>
                                                    <?php if (!empty($product['barcode'])): ?>
                                                        <span class="badge badge-barcode float-end">
                                                            <i class="bi bi-upc-scan"></i>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge badge-no-barcode float-end">
                                                            <i class="bi bi-x-circle"></i>
                                                        </span>
                                                    <?php endif; ?>
                                                </h5>
                                                <p class="card-text">
                                                    <small class="text-muted"><?php echo htmlspecialchars($product['description']); ?></small>
                                                </p>
                                                
                                                <form action="" method="POST" class="mt-3">
                                                    <input type="hidden" name="action" value="update_barcode">
                                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                    <div class="input-group">
                                                        <input type="text" name="barcode" class="form-control barcode-input" 
                                                               placeholder="Código de barras" 
                                                               value="<?php echo htmlspecialchars(isset($product['barcode']) ? $product['barcode'] : ''); ?>">
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="bi bi-check2"></i>
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                            <div class="card-footer bg-transparent">
                                                <small class="text-muted">Stock actual: <?php echo $product['stock']; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Instrucciones -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Instrucciones para la gestión de códigos de barras</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="bi bi-info-circle text-primary me-2"></i>¿Cómo asignar códigos de barras?</h6>
                                <ol>
                                    <li>Localice el producto al que desea asignar un código</li>
                                    <li>Ingrese el código en el campo de texto</li>
                                    <li>Haga clic en el botón <i class="bi bi-check2"></i> para guardar</li>
                                </ol>
                                <p>También puede utilizar un escáner para ingresar los códigos rápidamente.</p>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="bi bi-exclamation-triangle text-warning me-2"></i>Consideraciones importantes</h6>
                                <ul>
                                    <li>Los códigos deben ser únicos para cada producto</li>
                                    <li>Para eliminar un código, deje el campo en blanco y guarde</li>
                                    <li>Use la página de <a href="barcode_scanner.php">Escáner de Códigos</a> para dar de alta inventario usando los códigos asignados</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Destacar los campos con cambios
        var barcodeInputs = document.querySelectorAll('.barcode-input');
        
        barcodeInputs.forEach(function(input) {
            var originalValue = input.value;
            
            input.addEventListener('input', function() {
                if (this.value !== originalValue) {
                    this.classList.add('bg-warning', 'bg-opacity-25');
                } else {
                    this.classList.remove('bg-warning', 'bg-opacity-25');
                }
            });
        });

        // Añade esto dentro del script existente
        var generateButtons = document.querySelectorAll('.generate-barcode-btn');
        generateButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                var productId = this.getAttribute('data-product-id');
                var inputField = this.parentNode.querySelector('input[name="barcode"]');
                
                // Generar Code 128: ASF-XXXXX
                var barcode = 'ASF-' + productId.toString().padStart(5, '0');
                
                // Actualizar el campo
                inputField.value = barcode;
                
                // Resaltar el cambio
                inputField.classList.add('bg-warning', 'bg-opacity-25');
            });
        });
    });
    </script>
</body>
</html>