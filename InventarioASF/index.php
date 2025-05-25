<?php
//index.php
//session_start();
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


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_product':
                $inventory->addProduct(
                    $_POST['name'],
                    $_POST['description'],
                    (int)$_POST['stock']
                );
                $_SESSION['message'] = 'Producto agregado exitosamente';
                break;

                case 'update_stock':
                $inventory->updateStock(
                    $_POST['product_id'],
                    (int)$_POST['quantity'],
                    $_POST['operation'],
                    $_POST['notes']
                );
                $_SESSION['message'] = 'Stock actualizado exitosamente';
                break;

                case 'delete_product':
                $inventory->deleteProduct($_POST['product_id']);
                $_SESSION['message'] = 'Producto eliminado exitosamente';
                break;
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }

     // Usar JavaScript para redirigir en lugar de header()
    echo "<script>window.location.href = 'inventario.php';</script>";
    exit;
}

$products = $inventory->listProducts();

// Definir los productos por categoría
$acidificantesProducts = [
    'Acid tec',
    'Agroacid 19L',
    'Agroacid 1L',
    'Agroacid 250 ml',
    'Agroacid 5L',
    'Balance 19L',
    'Balance PH 1L',
    'Balance PH 5L'
];

$adherentesProducts = [
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
];

$enraizadoresProducts = [
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
];

$especializadosProducts = [
    'Promotor',
    'Gramineas 1kg 1ra etapa',
    'Gramineas 1pza 1ra etapa',
    'Gramineas 1pza 2da etapa',
    'Gramineas paq 1ra etapa',
    'Gramineas paq 2da etapa'
];

$microsProducts = [
    'CA+B',
    'Cab Zn-tec',
    'INIV',
    'Kurt 1L',
    'Kurt 20 lt',
    'Kurt 5L'
];

$npkProducts = [
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
];

$organicosProducts = [
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
];

$resinasProducts = [
    'Agro-cinnam',
    'Agroallium'
];

// Función para agrupar productos
function groupProducts($products, $acidificantesProducts, $adherentesProducts, $enraizadoresProducts, 
                    $especializadosProducts, $microsProducts, $npkProducts, $organicosProducts, $resinasProducts) {
    $grouped = [
        'Acidificantes' => [],
        'Adherentes' => [],
        'Enraizadores' => [],
        'Especializados' => [],
        'Micros' => [],
        'NPK' => [],
        'Orgánicos' => [],
        'Resinas' => [],
        'Otros' => []
    ];

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

// Actualizar la llamada a la función con las nuevas categorías
$groupedProducts = groupProducts($products, $acidificantesProducts, $adherentesProducts, $enraizadoresProducts, 
                              $especializadosProducts, $microsProducts, $npkProducts, $organicosProducts, $resinasProducts);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../dist/img/asf.png" />

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        :root {
            /* Colores principales (del logo) */
            --primary-green: #008F39;     /* Verde logo, ligeramente ajustado para mejor contraste */
            --primary-pink: #E6007E;      /* Magenta logo */
            
            /* Colores complementarios */
            --light-green: #E8F5E9;       /* Fondo claro verdoso */
            --dark-green: #005C24;        /* Verde oscuro para hover */
            --neutral-gray: #F5F5F5;      /* Gris muy claro para fondos */
            --text-dark: #2C3E50;         /* Gris oscuro para texto */
        }
        .product-card {
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .product-card .card-header {
            background-color: #f8f9fa;
        }
        .history-table {
            font-size: 0.875rem;
        }
        .history-table th,
        .history-table td {
            padding: 0.5rem;
        }
        .history-table thead {
            background-color: var(--light-green);
        }

        .product-toggle {
            cursor: pointer;
            display: block;
            width: 100%;
        }
        .product-toggle:hover {
            color: var(--primary-green) !important;
        }
        .collapse-icon {
            font-size: 0.8em;
            transition: transform 0.2s;
        }
        .collapsed .collapse-icon {
            transform: rotate(-90deg);
        }
        body {
            margin: 0;
            height: 100vh;
            background: var(--neutral-gray);
            color: var(--text-dark);
        }

        @keyframes gradient-animation {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        
        /* Sidebar */
        #wrapper {
            display: flex;
            overflow-x: hidden; /* Previene scroll horizontal */
        }
        #sidebar-wrapper {
            min-height: 100vh;
            width: 280px;
            margin-left: -280px;
            transition: margin 0.25s ease-out;
            background-color: white;
            border-right: 1px solid #E0E0E0;
            flex-shrink: 0; /* Previene que el sidebar se encoja */
        }

        .sidebar-heading {
            background-color: var(--primary-green);
            color: white;
        }

        .sidebar-link {
            color: var(--text-dark);
            border-left: 4px solid transparent;
        }

        .sidebar-link:hover {
            background-color: var(--light-green);
            border-left: 4px solid var(--primary-green);
            color: var(--primary-green);
        }
        #sidebar-wrapper .sidebar-heading {
            padding: 0.875rem 1.25rem;
            font-size: 1.2rem;
        }

        #wrapper.toggled #sidebar-wrapper {
            margin-left: 0;
        }

        #page-content-wrapper {
            flex: 1 1 auto; /* Permite que el contenido se ajuste al espacio disponible */
            width: 100%;
            min-width: 0; /* Permite que el contenido se encoja si es necesario */
        }


        @media (min-width: 768px) {
            #sidebar-wrapper {
                margin-left: 0;
            }

            #page-content-wrapper {
                min-width: 0;
                width: 100%;
            }

            #wrapper.toggled #sidebar-wrapper {
                margin-left: -280px;
            }
        }
        /* Botones */
        .btn-primary {
            background-color: var(--primary-green);
            border-color: var(--primary-green);
        }

        .btn-primary:hover {
            background-color: var(--dark-green);
            border-color: var(--dark-green);
        }

        .btn-success {
            background-color: var(--primary-green);
            border-color: var(--primary-green);
        }

        .text-success {
            color: var(--primary-green) !important;
        }

        .text-danger {
            color: var(--primary-pink) !important;
        }
        /* Alerts */
        .alert-success {
            background-color: var(--light-green);
            border-color: var(--primary-green);
            color: var(--dark-green);
        }
        #sidebar {
            min-width: 250px;
            max-width: 280px;
            min-height: 120vh;
            font-family: 'Poppins', sans-serif;
            background: #28a745;
            color: #fff;
            transition: all 0.3s;
        }

        .logo {
            text-decoration: none;
            color: #fff;
            font-size: 24px;
            font-weight: 700;
            padding: 1.5rem;
            display: block;
        }

        .logo span {
            font-size: 13px;
            font-weight: 400;
            display: block;
            margin-top: 5px;
        }

        .list-unstyled {
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .list-unstyled li {
            padding: 0.5rem 1.5rem;
        }

        .list-unstyled li a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            padding: 10px 0;
            display: block;
            font-size: 16px;
            transition: all 0.3s;
        }

        .list-unstyled li a:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
            padding-left: 10px;
        }

        .list-unstyled li.active a {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
        }

        .fa {
            margin-right: 15px;
            width: 20px;
            text-align: center;
        }

        .footer {
            padding: 1.5rem;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
            position: absolute;
            bottom: 0;
            width: 100%;
        }
        .logo-container {
            padding: 20px;
            text-align: center;
        }

        .logo-circle {
            width: 120px;
            height: 120px;
            background-color: white;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .sidebar-logo {
            max-width: 140px;
            height: auto;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Page Content -->
        <div id="page-content-wrapper">
            <div class="container-fluid py-4">
                <!-- Mensajes -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        echo $_SESSION['message'];
                        unset($_SESSION['message']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>


                <div class="card mb-4">
                    <div class="card-header">
                        <h4>Buscar Productos</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <input type="text" id="searchInput" class="form-control" placeholder="Buscar por nombre o descripción...">
                            </div>
                            <div class="col-md-2 mb-3">
                                <select id="categoryFilter" class="form-select">
                                    <option value="all">Todas las categorías</option>
                                    <?php foreach ($groupTitles as $groupKey => $groupTitle): ?>
                                        <option value="<?php echo strtolower($groupKey); ?>"><?php echo $groupTitle; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <button id="searchButton" class="btn btn-primary w-100">Buscar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <?php
                    if(isset($_SESSION['role']) && $_SESSION['role'] == "admin"){
                        echo '
                            <!-- Agregar Producto -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h4>Agregar Nuevo Producto</h4>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="add_product">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label>Nombre</label>
                                                <input type="text" name="name" class="form-control" required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label>Categoría</label>
                                                <input type="text" name="description" class="form-control" required>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label>Stock Inicial</label>
                                                <input type="number" name="stock" class="form-control" required min="0">
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label>&nbsp;</label>
                                                <button type="submit" class="btn btn-primary w-100">Agregar</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        ';
                    }
                ?>

                <!-- Lista de Productos -->
                <h4 class="mb-3">Productos en Inventario</h4>
                <?php
                    $groupTitles = [
                        'Acidificantes' => 'Acidificantes',
                        'Adherentes' => 'Adherentes',
                        'Enraizadores' => 'Enraizadores',
                        'Especializados' => 'Especializados',
                        'Micros' => 'Micronutrientes',
                        'NPK' => 'N-P-K',
                        'Orgánicos' => 'Orgánicos',
                        'Resinas' => 'Resinas',
                        'Otros' => 'Otros Productos'
                    ];

                    foreach ($groupTitles as $groupKey => $groupTitle):
                        if (!empty($groupedProducts[$groupKey])):
                            ?>
                            <!-- Group -->
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <a href="#" class="text-decoration-none text-dark product-toggle" data-bs-toggle="collapse" data-bs-target="#<?php echo strtolower($groupKey); ?>-group">
                                            <?php echo $groupTitle; ?>
                                            <small class="text-muted ms-2">
                                                <i class="collapse-icon">▼</i>
                                            </small>
                                        </a>
                                    </h5>
                                </div>
                                <div class="collapse show" id="<?php echo strtolower($groupKey); ?>-group">
                                    <div class="card-body">
                                        <div class="row row-cols-1 row-cols-md-3 g-4">
                                            <?php foreach ($groupedProducts[$groupKey] as $product): ?>
                                                <div class="col">
                                                    <div class="card h-100">
                                                        <div class="card-header d-flex justify-content-between align-items-center">
                                                            <h5 class="mb-0">
                                                                <a href="#" class="text-decoration-none text-dark product-toggle" data-bs-toggle="collapse" data-bs-target="#product-<?php echo $product['id']; ?>">
                                                                    <?php echo htmlspecialchars($product['name']); ?>
                                                                    <small class="text-muted ms-2">
                                                                        <i class="collapse-icon">▼</i>
                                                                    </small>
                                                                </a>
                                                            </h5>
                                                            <?php
                                                            if(isset($_SESSION['role']) && $_SESSION['role'] == "admin"){
                                                                echo '
                                                                <form method="POST" class="d-inline" onsubmit="return confirm(\'¿Eliminar este producto?\')">
                                                                <input type="hidden" name="action" value="delete_product">
                                                                <input type="hidden" name="product_id" value="' . $product['id'] . '">
                                                                <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                                                </form>
                                                                ';
                                                            }
                                                            ?>
                                                        </div>
                                                        <div class="collapse show" id="product-<?php echo $product['id']; ?>">
                                                            <div class="card-body">
                                                                <p><?php echo htmlspecialchars($product['description']); ?></p>
                                                                <p>
                                                                    <strong>Stock actual:</strong> <?php echo $product['stock']; ?>
                                                                </p>

                                                                <!-- Formulario de Actualización de Stock -->
                                                                <form method="POST" class="mb-3">
                                                                    <input type="hidden" name="action" value="update_stock">
                                                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                                    <div class="row g-2">
                                                                        <div class="col-md-3">
                                                                            <select name="operation" class="form-select form-select-sm">
                                                                                <option value="add">Entrada</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="col-md-3">
                                                                            <input type="number" name="quantity" class="form-control form-control-sm" placeholder="Cantidad" required min="1">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="Notas">
                                                                        </div>
                                                                        <div class="col-md-2">
                                                                            <button type="submit" class="btn btn-success btn-sm w-100">✓</button>
                                                                        </div>
                                                                    </div>
                                                                </form>

                                                                <!-- Historial -->
                                                                <?php $history = $inventory->getProductHistory($product['id']); ?>
                                                                <div class="table-responsive">
                                                                    <table class="table table-sm history-table">
                                                                        <thead>
                                                                            <tr>
                                                                                <th>Fecha</th>
                                                                                <th>Operación</th>
                                                                                <th>Cantidad</th>
                                                                                <th>Notas</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <?php foreach ($history as $record): ?>
                                                                                <tr>
                                                                                    <td><?php echo $record['timestamp']; ?></td>
                                                                                    <td>
                                                                                        <?php 
                                                                                            $operation = $record['operation'];
                                                                                            switch($operation) {
                                                                                                case 'add':
                                                                                                    echo '<span class="text-success">Entrada</span>';
                                                                                                    break;
                                                                                                case 'remove':
                                                                                                    echo '<span class="text-danger">Salida</span>';
                                                                                                    break;
                                                                                                case 'delete':
                                                                                                    echo '<span class="text-danger">Eliminación</span>';
                                                                                                    break;
                                                                                                default:
                                                                                                    echo htmlspecialchars($operation);
                                                                                            }
                                                                                        ?>
                                                                                    </td>
                                                                                    <td><?php echo $record['quantity']; ?></td>
                                                                                    <td><?php echo htmlspecialchars($record['notes']); ?></td>
                                                                                </tr>
                                                                            <?php endforeach; ?>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php 
                        endif;
                    endforeach; 
                ?>
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // Toggle del sidebar
                        const menuToggle = document.getElementById('menu-toggle');
                        if (menuToggle) {
                            menuToggle.addEventListener('click', function(e) {
                                e.preventDefault();
                                document.getElementById('wrapper').classList.toggle('toggled');
                            });
                        }

                        // Manejar el comportamiento de las tarjetas de productos
                        document.querySelectorAll('.product-toggle').forEach(function(toggle) {
                            const targetId = toggle.getAttribute('data-bs-target');
                            const collapseEl = document.querySelector(targetId);
                            const iconEl = toggle.querySelector('.collapse-icon');
                            
                            // Restaurar estado guardado
                            const isCollapsed = localStorage.getItem('collapse-' + targetId.substring(1)) === 'true';
                            if (isCollapsed && collapseEl) {
                                collapseEl.classList.remove('show');
                                toggle.classList.add('collapsed');
                                if (iconEl) iconEl.textContent = '▶';
                            }

                            // Manejar clic en el toggle
                            toggle.addEventListener('click', function(e) {
                                e.preventDefault();
                                const isCollapsed = !collapseEl.classList.contains('show');
                                
                                // Actualizar el ícono
                                if (iconEl) {
                                    iconEl.textContent = isCollapsed ? '▼' : '▶';
                                }
                                
                                // Guardar estado en localStorage
                                localStorage.setItem('collapse-' + targetId.substring(1), !isCollapsed);
                            });

                            // Escuchar eventos de Bootstrap collapse
                            if (collapseEl) {
                                collapseEl.addEventListener('hidden.bs.collapse', function() {
                                    if (iconEl) iconEl.textContent = '▶';
                                    localStorage.setItem('collapse-' + this.id, 'true');
                                });

                                collapseEl.addEventListener('shown.bs.collapse', function() {
                                    if (iconEl) iconEl.textContent = '▼';
                                    localStorage.setItem('collapse-' + this.id, 'false');
                                });
                            }
                        });
                    });
                </script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // Referencias a los elementos del DOM
                        const searchInput = document.getElementById('searchInput');
                        const categoryFilter = document.getElementById('categoryFilter');
                        const searchButton = document.getElementById('searchButton');
                        
                        // Función para filtrar productos
                        function filterProducts() {
                            const searchTerm = searchInput.value.toLowerCase();
                            const category = categoryFilter.value;
                            
                            // Obtener SOLO las cards que corresponden a grupos de productos
                            // Modificamos el selector para ser más específico y excluir otras cards
                            const groupCards = document.querySelectorAll('.card.mb-4:has(.product-toggle[data-bs-target])');
                            
                            // Si el selector :has no es compatible con el navegador, usar esta alternativa:
                            if (groupCards.length === 0) {
                                // Alternativa para navegadores que no soportan :has
                                const allCards = document.querySelectorAll('.card.mb-4');
                                allCards.forEach(card => {
                                    // Solo procesar cards que tengan un elemento collapse con ID que termine en -group
                                    const collapseEl = card.querySelector('.collapse');
                                    if (!collapseEl || !collapseEl.id || !collapseEl.id.endsWith('-group')) return;
                                    
                                    const groupId = collapseEl.id;
                                    const groupName = groupId.replace('-group', '');
                                    
                                    // Aplicar filtro de categoría
                                    if (category !== 'all' && category !== groupName) {
                                        card.style.display = 'none';
                                        return;
                                    } else {
                                        card.style.display = 'block';
                                    }
                                    
                                    // Aplicar filtro de búsqueda
                                    if (searchTerm) {
                                        const productCards = card.querySelectorAll('.col .card');
                                        let visibleProducts = 0;
                                        
                                        productCards.forEach(productCard => {
                                            const productNameEl = productCard.querySelector('h5 a');
                                            if (!productNameEl) return;
                                            
                                            const productName = productNameEl.innerText.toLowerCase();
                                            const productDescEl = productCard.querySelector('.card-body p:first-child');
                                            const productDesc = productDescEl ? productDescEl.innerText.toLowerCase() : '';
                                            
                                            if (productName.includes(searchTerm) || productDesc.includes(searchTerm)) {
                                                productCard.closest('.col').style.display = 'block';
                                                visibleProducts++;
                                            } else {
                                                productCard.closest('.col').style.display = 'none';
                                            }
                                        });
                                        
                                        if (visibleProducts === 0) {
                                            card.style.display = 'none';
                                        }
                                    } else {
                                        // Si no hay término de búsqueda, mostrar todos los productos
                                        const productCards = card.querySelectorAll('.col');
                                        productCards.forEach(productCol => {
                                            productCol.style.display = 'block';
                                        });
                                    }
                                });
                                return; // Terminar la función si usamos el método alternativo
                            }
                            
                            // Si el selector :has funciona, continuamos con este código
                            groupCards.forEach(groupCard => {
                                // De forma segura, obtener el ID del grupo
                                const collapseEl = groupCard.querySelector('.collapse');
                                if (!collapseEl || !collapseEl.id) return;
                                
                                const groupId = collapseEl.id;
                                if (!groupId.endsWith('-group')) return;
                                
                                const groupName = groupId.replace('-group', '');
                                
                                // Si se ha seleccionado una categoría y no es "all" y no coincide con el grupo actual, ocultar todo el grupo
                                if (category !== 'all' && category !== groupName) {
                                    groupCard.style.display = 'none';
                                    return;
                                } else {
                                    groupCard.style.display = 'block';
                                }
                                
                                // Si hay término de búsqueda, filtrar productos dentro del grupo
                                if (searchTerm) {
                                    // Obtener todos los productos en este grupo
                                    const productCards = groupCard.querySelectorAll('.col .card');
                                    let visibleProducts = 0;
                                    
                                    // Recorrer cada producto de forma segura
                                    productCards.forEach(productCard => {
                                        const productNameEl = productCard.querySelector('h5 a');
                                        if (!productNameEl) return;
                                        
                                        const productName = productNameEl.innerText.toLowerCase();
                                        const productDescEl = productCard.querySelector('.card-body p:first-child');
                                        const productDesc = productDescEl ? productDescEl.innerText.toLowerCase() : '';
                                        
                                        // Verificar si coincide con la búsqueda
                                        if (productName.includes(searchTerm) || productDesc.includes(searchTerm)) {
                                            productCard.closest('.col').style.display = 'block';
                                            visibleProducts++;
                                        } else {
                                            productCard.closest('.col').style.display = 'none';
                                        }
                                    });
                                    
                                    // Si no hay productos visibles en este grupo, ocultar el grupo
                                    if (visibleProducts === 0) {
                                        groupCard.style.display = 'none';
                                    }
                                } else {
                                    // Si no hay término de búsqueda, mostrar todos los productos
                                    const productCards = groupCard.querySelectorAll('.col');
                                    productCards.forEach(productCol => {
                                        productCol.style.display = 'block';
                                    });
                                }
                            });
                        }
                        
                        // Event listeners
                        searchButton.addEventListener('click', filterProducts);
                        
                        // También filtrar al presionar Enter en el campo de búsqueda
                        searchInput.addEventListener('keyup', function(event) {
                            if (event.key === 'Enter') {
                                filterProducts();
                            }
                        });
                        
                        // Filtrar cuando cambia la categoría
                        categoryFilter.addEventListener('change', filterProducts);
                        
                        // Limpiar filtros cuando se borra el campo de búsqueda
                        searchInput.addEventListener('input', function() {
                            if (this.value === '') {
                                filterProducts();
                            }
                        });
                    });
                    </script>
</body>
</html>