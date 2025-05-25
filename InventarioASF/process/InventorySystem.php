<?php
// InventorySystem.php
class InventorySystem {
    private $db;

    public function __construct() {
        try {
            // Verificar si SQLite3 está habilitado
            if (!class_exists('SQLite3')) {
                throw new Exception('SQLite3 no está habilitado en PHP');
            }

            // Verificar permisos y existencia del directorio
            $dbPath = 'assets/db';
            if (!file_exists($dbPath)) {
                if (!mkdir($dbPath, 0755, true)) {
                    throw new Exception('No se pudo crear el directorio de la base de datos');
                }
            }

            // Verificar permisos de escritura
            if (!is_writable($dbPath)) {
                throw new Exception('El directorio de la base de datos no tiene permisos de escritura');
            }

            // Intentar conectar a la base de datos
            $this->db = new SQLite3($dbPath . '/inventory.db');
            
            // Configurar el modo de errores de SQLite
            $this->db->enableExceptions(true);
            
             // Forzar la zona horaria de México Central
            if (!ini_get('date.timezone')) {
                date_default_timezone_set('America/Mexico_City');
            }
            
            // Verificar la zona horaria actual
            $currentTimezone = date_default_timezone_get();
            error_log("Zona horaria actual: " . $currentTimezone);
            
            $this->createTables();
            
            
        } catch (Exception $e) {
            error_log("Error en InventorySystem constructor: " . $e->getMessage());
            throw $e;
        }
    }

    private function getCurrentDateTime() {
        // Crear un objeto DateTime con la zona horaria explícita
        $dateTime = new DateTime('now', new DateTimeZone('America/Mexico_City'));
        error_log("Hora actual generada: " . $dateTime->format('Y-m-d H:i:s'));
        return $dateTime->format('Y-m-d H:i:s');
    }

    private function createTables() {
        try {
            $this->db->exec('BEGIN TRANSACTION');

            // Tabla de productos
            $this->db->exec('
                CREATE TABLE IF NOT EXISTS products (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    description TEXT,
                    stock INTEGER DEFAULT 0,
                    created_at DATETIME,
                    updated_at DATETIME
                )
            ');

            // Tabla de historial
            $this->db->exec('
                CREATE TABLE IF NOT EXISTS history (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    product_id INTEGER,
                    operation TEXT,
                    quantity INTEGER,
                    notes TEXT,
                    timestamp DATETIME,
                    FOREIGN KEY (product_id) REFERENCES products(id)
                )
            ');

            $this->db->exec('COMMIT');
        } catch (Exception $e) {
            $this->db->exec('ROLLBACK');
            error_log("Error creando tablas: " . $e->getMessage());
            throw $e;
        }
    }

    public function addProduct($name, $description, $initialStock) {
        $currentTime = $this->getCurrentDateTime();
        error_log("Insertando producto con timestamp: " . $currentTime);
        
        $stmt = $this->db->prepare('
            INSERT INTO products (name, description, stock, created_at, updated_at)
            VALUES (:name, :description, :stock, :created_at, :updated_at)
        ');
        
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':description', $description, SQLITE3_TEXT);
        $stmt->bindValue(':stock', $initialStock, SQLITE3_INTEGER);
        $stmt->bindValue(':created_at', $currentTime, SQLITE3_TEXT);
        $stmt->bindValue(':updated_at', $currentTime, SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            $productId = $this->db->lastInsertRowID();
            $this->recordHistory($productId, 'add', $initialStock, "Producto creado con stock inicial");
            return $productId;
        }
        return false;
    }

    public function updateStock($productId, $quantity, $type = 'add', $notes = '') {
        $product = $this->getProduct($productId);
        if (!$product) {
            throw new Exception("Producto no encontrado");
        }

        $newStock = ($type === 'add') 
            ? $product['stock'] + $quantity 
            : $product['stock'] - $quantity;

        if ($newStock < 0) {
            throw new Exception("Stock insuficiente");
        }

        $currentTime = $this->getCurrentDateTime();
        error_log("Actualizando stock con timestamp: " . $currentTime);
        
        $stmt = $this->db->prepare('
            UPDATE products 
            SET stock = :stock, updated_at = :updated_at 
            WHERE id = :id
        ');
        
        $stmt->bindValue(':stock', $newStock, SQLITE3_INTEGER);
        $stmt->bindValue(':updated_at', $currentTime, SQLITE3_TEXT);
        $stmt->bindValue(':id', $productId, SQLITE3_INTEGER);
        
        if ($stmt->execute()) {
            $this->recordHistory($productId, $type, $quantity, $notes);
            return true;
        }
        return false;
    }

    private function recordHistory($productId, $operation, $quantity, $notes) {
        $currentTime = $this->getCurrentDateTime();
        error_log("Registrando historial con timestamp: " . $currentTime);
        
        $stmt = $this->db->prepare('
            INSERT INTO history (product_id, operation, quantity, notes, timestamp)
            VALUES (:product_id, :operation, :quantity, :notes, :timestamp)
        ');
        
        $stmt->bindValue(':product_id', $productId, SQLITE3_INTEGER);
        $stmt->bindValue(':operation', $operation, SQLITE3_TEXT);
        $stmt->bindValue(':quantity', $quantity, SQLITE3_INTEGER);
        $stmt->bindValue(':notes', $notes, SQLITE3_TEXT);
        $stmt->bindValue(':timestamp', $currentTime, SQLITE3_TEXT);
        
        return $stmt->execute();
    }

    public function getProductHistory($productId) {
        $result = $this->db->query("
            SELECT * FROM history 
            WHERE product_id = $productId 
            ORDER BY timestamp DESC
        ");
        
        $history = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $history[] = $row;
        }
        return $history;
    }

    public function deleteProduct($productId) {
        $stmt = $this->db->prepare('DELETE FROM products WHERE id = :id');
        $stmt->bindValue(':id', $productId, SQLITE3_INTEGER);
        
        if ($stmt->execute()) {
            $this->recordHistory($productId, 'delete', 0, "Producto eliminado del sistema");
            return true;
        }
        return false;
    }

    public function listProducts() {
        try {
            $this->checkConnection();
            
            $result = $this->db->query('SELECT * FROM products ORDER BY name');
            if (!$result) {
                throw new Exception('Error al consultar productos');
            }
            
            $products = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $products[] = $row;
            }
            
            error_log("Productos recuperados: " . count($products));
            return $products;
            
        } catch (Exception $e) {
            error_log("Error en listProducts: " . $e->getMessage());
            throw $e;
        }
    }

    public function getProduct($id) {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE id = :id');
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC);
    }

    private function checkConnection() {
        if (!$this->db) {
            throw new Exception('La conexión a la base de datos no está establecida');
        }
        
        try {
            $result = $this->db->query('SELECT 1');
            if (!$result) {
                throw new Exception('No se puede ejecutar consultas en la base de datos');
            }
        } catch (Exception $e) {
            error_log("Error de conexión: " . $e->getMessage());
            throw $e;
        }
    }

    private function checkDatabasePermissions() {
        $dbFile = 'assets/db/inventory.db';
        if (file_exists($dbFile)) {
            if (!is_readable($dbFile)) {
                throw new Exception('La base de datos no tiene permisos de lectura');
            }
            if (!is_writable($dbFile)) {
                throw new Exception('La base de datos no tiene permisos de escritura');
            }
        }
    }

}