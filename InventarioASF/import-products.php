<?php
// import-products.php
// Script para importar los nuevos productos a la base de datos

// Configuración de la conexión a la base de datos
$host = 'localhost';
$dbname = 'agrosant_pedidos';
$username = 'root';
$password = '';

try {
    // Crear conexión PDO
    $db = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    echo "Conexión establecida correctamente.<br>";
    
    // Definir los productos por categoría
    $products = [
        // ACIDIFICANTES
        ['name' => 'Acid tec', 'description' => 'Acidificante', 'category' => 'ACIDIFICANTE'],
        ['name' => 'Agroacid 19L', 'description' => 'Acidificante', 'category' => 'ACIDIFICANTE'],
        ['name' => 'Agroacid 1L', 'description' => 'Acidificante', 'category' => 'ACIDIFICANTE'],
        ['name' => 'Agroacid 250 ml', 'description' => 'Acidificante', 'category' => 'ACIDIFICANTE'],
        ['name' => 'Agroacid 5L', 'description' => 'Acidificante', 'category' => 'ACIDIFICANTE'],
        ['name' => 'Balance 19L', 'description' => 'Acidificante', 'category' => 'ACIDIFICANTE'],
        ['name' => 'Balance PH 1L', 'description' => 'Acidificante', 'category' => 'ACIDIFICANTE'],
        ['name' => 'Balance PH 5L', 'description' => 'Acidificante', 'category' => 'ACIDIFICANTE'],
        
        // ADHERENTES
        ['name' => 'Adherex', 'description' => 'Adherente', 'category' => 'ADHERENTE'],
        ['name' => 'Adhetec 1 lt', 'description' => 'Adherente', 'category' => 'ADHERENTE'],
        ['name' => 'Adheval 19L', 'description' => 'Adherente', 'category' => 'ADHERENTE'],
        ['name' => 'Adheval 1L', 'description' => 'Adherente', 'category' => 'ADHERENTE'],
        ['name' => 'Adheval 5L', 'description' => 'Adherente', 'category' => 'ADHERENTE'],
        ['name' => 'Agro ADH 19L', 'description' => 'Adherente', 'category' => 'ADHERENTE'],
        ['name' => 'Agro ADH 1L', 'description' => 'Adherente', 'category' => 'ADHERENTE'],
        ['name' => 'Agro ADH 20 lt', 'description' => 'Adherente', 'category' => 'ADHERENTE'],
        ['name' => 'Agro ADH 250ml', 'description' => 'Adherente', 'category' => 'ADHERENTE'],
        ['name' => 'Agro ADH 5L', 'description' => 'Adherente', 'category' => 'ADHERENTE'],
        ['name' => 'Agro ADHR', 'description' => 'Adherente', 'category' => 'ADHERENTE'],
        ['name' => 'Tecnoadher', 'description' => 'Adherente', 'category' => 'ADHERENTE'],
        ['name' => 'Tecnoadher 19 lt', 'description' => 'Adherente', 'category' => 'ADHERENTE'],
        ['name' => 'tecnoadher 5 lt', 'description' => 'Adherente', 'category' => 'ADHERENTE'],
        
        // ENRAIZADORES
        ['name' => '13/11/1933', 'description' => 'Enraizador', 'category' => 'ENRAIZADOR'],
        ['name' => '10-10-35 (20L)', 'description' => 'Enraizador', 'category' => 'ENRAIZADOR'],
        ['name' => 'oct-35', 'description' => 'Enraizador', 'category' => 'ENRAIZADOR'],
        ['name' => '13-43-13', 'description' => 'Enraizador', 'category' => 'ENRAIZADOR'],
        ['name' => '8-24-00 1L', 'description' => 'Enraizador', 'category' => 'ENRAIZADOR'],
        ['name' => '8-24-00 20L', 'description' => 'Enraizador', 'category' => 'ENRAIZADOR'],
        ['name' => '8-24-00 5L', 'description' => 'Enraizador', 'category' => 'ENRAIZADOR'],
        ['name' => '8-24-8 (20L)', 'description' => 'Enraizador', 'category' => 'ENRAIZADOR'],
        ['name' => '8-24-8 1L', 'description' => 'Enraizador', 'category' => 'ENRAIZADOR'],
        ['name' => '8-24-8 5L', 'description' => 'Enraizador', 'category' => 'ENRAIZADOR'],
        ['name' => 'Montze', 'description' => 'Enraizador', 'category' => 'ENRAIZADOR'],
        ['name' => 'N-40', 'description' => 'Enraizador', 'category' => 'ENRAIZADOR'],
        ['name' => 'N-44', 'description' => 'Enraizador', 'category' => 'ENRAIZADOR'],
        ['name' => 'Koren', 'description' => 'Enraizador', 'category' => 'ENRAIZADOR'],
        
        // ESPECIALIZADOS
        ['name' => 'Promotor', 'description' => 'Producto especializado', 'category' => 'ESPECIALIZADOS'],
        ['name' => 'Gramineas 1kg 1ra etapa', 'description' => 'Producto especializado', 'category' => 'ESPECIALIZADOS'],
        ['name' => 'Gramineas 1pza 1ra etapa', 'description' => 'Producto especializado', 'category' => 'ESPECIALIZADOS'],
        ['name' => 'Gramineas 1pza 2da etapa', 'description' => 'Producto especializado', 'category' => 'ESPECIALIZADOS'],
        ['name' => 'Gramineas paq 1ra etapa', 'description' => 'Producto especializado', 'category' => 'ESPECIALIZADOS'],
        ['name' => 'Gramineas paq 2da etapa', 'description' => 'Producto especializado', 'category' => 'ESPECIALIZADOS'],
        
        // MICRONUTRIENTES
        ['name' => 'CA+B', 'description' => 'Micronutriente', 'category' => 'MICROS'],
        ['name' => 'Cab Zn-tec', 'description' => 'Micronutriente', 'category' => 'MICROS'],
        ['name' => 'INIV', 'description' => 'Micronutriente', 'category' => 'MICROS'],
        ['name' => 'Kurt 1L', 'description' => 'Micronutriente', 'category' => 'MICROS'],
        ['name' => 'Kurt 20 lt', 'description' => 'Micronutriente', 'category' => 'MICROS'],
        ['name' => 'Kurt 5L', 'description' => 'Micronutriente', 'category' => 'MICROS'],
        
        // N-P-K
        ['name' => '00-50 10 kg', 'description' => 'Fertilizante N-P-K', 'category' => 'N-P-K'],
        ['name' => '00-50-CA', 'description' => 'Fertilizante N-P-K', 'category' => 'N-P-K'],
        ['name' => 'oct-60', 'description' => 'Fertilizante N-P-K', 'category' => 'N-P-K'],
        ['name' => '20-30-10', 'description' => 'Fertilizante N-P-K', 'category' => 'N-P-K'],
        ['name' => 'Aguacate 1 kg', 'description' => 'Fertilizante N-P-K', 'category' => 'N-P-K'],
        ['name' => 'Hass', 'description' => 'Fertilizante N-P-K', 'category' => 'N-P-K'],
        ['name' => 'Llenado', 'description' => 'Fertilizante N-P-K', 'category' => 'N-P-K'],
        ['name' => 'T-20', 'description' => 'Fertilizante N-P-K', 'category' => 'N-P-K'],
        ['name' => 'Chayote', 'description' => 'Fertilizante N-P-K', 'category' => 'N-P-K'],
        ['name' => 'Crecifrut', 'description' => 'Fertilizante N-P-K', 'category' => 'N-P-K'],
        ['name' => 'DAP K', 'description' => 'Fertilizante N-P-K', 'category' => 'N-P-K'],
        ['name' => 'Durazno 1 kg', 'description' => 'Fertilizante N-P-K', 'category' => 'N-P-K'],
        ['name' => 'Guayaba 1 kg', 'description' => 'Fertilizante N-P-K', 'category' => 'N-P-K'],
        ['name' => 'Nitromax', 'description' => 'Fertilizante N-P-K', 'category' => 'N-P-K'],
        ['name' => 'Nutri HASS', 'description' => 'Fertilizante N-P-K', 'category' => 'N-P-K'],
        ['name' => 'Nutrival', 'description' => 'Fertilizante N-P-K', 'category' => 'N-P-K'],
        ['name' => 'Tecno 20-20', 'description' => 'Fertilizante N-P-K', 'category' => 'N-P-K'],
        ['name' => 'Tecno balance 20-20-20', 'description' => 'Fertilizante N-P-K', 'category' => 'N-P-K'],
        ['name' => 'Tecno urea 1 kg', 'description' => 'Fertilizante N-P-K', 'category' => 'N-P-K'],
        ['name' => 'tecnogreen 1 kg', 'description' => 'Fertilizante N-P-K', 'category' => 'N-P-K'],
        ['name' => 'tecnogrow 20 lt', 'description' => 'Fertilizante N-P-K', 'category' => 'N-P-K'],
        ['name' => 'tecnogrow 5 lt', 'description' => 'Fertilizante N-P-K', 'category' => 'N-P-K'],
        ['name' => 'Tenco 20-30-10', 'description' => 'Fertilizante N-P-K', 'category' => 'N-P-K'],
        ['name' => 'Zarzamora 1 kg', 'description' => 'Fertilizante N-P-K', 'category' => 'N-P-K'],
        
        // ORGÁNICOS
        ['name' => 'Fast 15kg', 'description' => 'Producto orgánico', 'category' => 'ORGANICOS'],
        ['name' => 'Fast 200gr', 'description' => 'Producto orgánico', 'category' => 'ORGANICOS'],
        ['name' => 'Fast 750gr', 'description' => 'Producto orgánico', 'category' => 'ORGANICOS'],
        ['name' => 'Fulvik', 'description' => 'Producto orgánico', 'category' => 'ORGANICOS'],
        ['name' => 'Fulvik 750gr', 'description' => 'Producto orgánico', 'category' => 'ORGANICOS'],
        ['name' => 'Humik 15 kg', 'description' => 'Producto orgánico', 'category' => 'ORGANICOS'],
        ['name' => 'Humik 450 gr', 'description' => 'Producto orgánico', 'category' => 'ORGANICOS'],
        ['name' => 'Volvox 15kg', 'description' => 'Producto orgánico', 'category' => 'ORGANICOS'],
        ['name' => 'Volvox 1kg', 'description' => 'Producto orgánico', 'category' => 'ORGANICOS'],
        ['name' => 'Aminomax', 'description' => 'Producto orgánico', 'category' => 'ORGANICOS'],
        ['name' => 'Stimulation', 'description' => 'Producto orgánico', 'category' => 'ORGANICOS'],
        
        // RESINAS
        ['name' => 'Agro-cinnam', 'description' => 'Resina', 'category' => 'RESINA'],
        ['name' => 'Agroallium', 'description' => 'Resina', 'category' => 'RESINA']
    ];
    
    // Preparar la sentencia de inserción
    $stmt = $db->prepare("
        INSERT INTO products (name, description, stock, created_at, updated_at) 
        VALUES (:name, :description, 0, NOW(), NOW())
    ");
    
    // Contador de productos insertados
    $insertCount = 0;
    
    // Insertar cada producto
    foreach ($products as $product) {
        $stmt->bindParam(':name', $product['name']);
        $stmt->bindParam(':description', $product['description']);
        $stmt->execute();
        $insertCount++;
    }
    
    echo "Se han insertado $insertCount productos exitosamente.";
    
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}