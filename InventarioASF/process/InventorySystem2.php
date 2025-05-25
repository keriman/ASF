<?php
//process/InventorySystem2.php
class InventorySystem {
    private $db;

    public function __construct() {
        try {
            // Configuración de la conexión MySQL
            $host = 'localhost';
            $dbname = 'agrosant_pedidos';
            $username = 'root';
            $password = '';
            
            // Crear conexión usando PDO
            $this->db = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            
            date_default_timezone_set('America/Mexico_City');
            
        } catch (PDOException $e) {
            error_log("Error en InventorySystem constructor: " . $e->getMessage());
            throw $e;
        }
    }

    public function listProducts() {
        try {
            $stmt = $this->db->query('SELECT * FROM products ORDER BY name');
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en listProducts: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function listProductsQuinagro() {
        try {
            $stmt = $this->db->query('SELECT * FROM products_quinagro ORDER BY name');
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en listProductsQuinagro: " . $e->getMessage());
            throw $e;
        }
    }

    public function getProduct($id) {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Obtiene un producto por su código de barras
     * @param string $barcode El código de barras a buscar
     * @return array|null El producto encontrado o null si no existe
     */
    public function getProductByBarcode($barcode) {
        try {
            $stmt = $this->db->prepare('SELECT * FROM products WHERE barcode = :barcode');
            $stmt->execute([':barcode' => $barcode]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error en getProductByBarcode: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Asigna un código de barras a un producto
     * @param int $productId ID del producto
     * @param string $barcode Código de barras a asignar
     * @return bool True si se asignó correctamente
     */
    // En InventorySystem2.php, modifica la función assignBarcode:

    public function assignBarcode($productId, $barcode = null) {
        try {
            // Verificar que el producto existe
            $product = $this->getProduct($productId);
            if (!$product) {
                throw new Exception("Producto no encontrado");
            }
            
            // Si no se proporciona un código o está vacío, generar uno Code 128 compatible
            if ($barcode === null || trim($barcode) === '') {
                // Formato Code 128: ASF-XXXXX (donde XXXXX es el ID del producto)
                $barcode = 'ASF-' . str_pad($productId, 5, '0', STR_PAD_LEFT);
            }
            
            // Verificar si el código de barras ya está asignado a otro producto
            $existingProduct = $this->getProductByBarcode($barcode);
            if ($existingProduct && $existingProduct['id'] != $productId) {
                throw new Exception("El código de barras ya está asignado a otro producto");
            }
            
            // Actualizar el código de barras
            $stmt = $this->db->prepare('
                UPDATE products 
                SET barcode = :barcode, updated_at = :updated_at 
                WHERE id = :id
            ');
            
            $currentTime = date('Y-m-d H:i:s');
            return $stmt->execute(array(
                ':barcode' => $barcode,
                ':updated_at' => $currentTime,
                ':id' => $productId
            ));
            
        } catch (Exception $e) {
            error_log("Error en assignBarcode: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateStock($productId, $quantity, $type = 'add', $notes = '') {
        try {
            $product = $this->getProduct($productId);
            if (!$product) {
                throw new Exception("Producto no encontrado");
            }

            $newStock = 0;
            if ($type === 'add') {
                $newStock = $product['stock'] + $quantity;
            } elseif ($type === 'Salida' || $type === 'subtract') {
                // Permitir tanto "Salida" como "subtract" para compatibilidad
                $newStock = $product['stock'] - $quantity;
            } else {
                // Para compatibilidad con el código anterior
                $newStock = ($type === 'add') 
                    ? $product['stock'] + $quantity 
                    : $product['stock'] - $quantity;
            }

            if ($newStock < 0) {
                throw new Exception("Stock insuficiente. Stock actual: {$product['stock']}, cantidad solicitada: {$quantity}");
            }

            $currentTime = date('Y-m-d H:i:s');
            
            $stmt = $this->db->prepare('
                UPDATE products 
                SET stock = :stock, updated_at = :updated_at 
                WHERE id = :id
            ');
            
            $success = $stmt->execute([
                ':stock' => $newStock,
                ':updated_at' => $currentTime,
                ':id' => $productId
            ]);
            
            if ($success) {
                $this->recordHistory($productId, $type, $quantity, $notes);
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Error en updateStock: " . $e->getMessage());
            throw $e;
        }
    }

    private function recordHistory($productId, $operation, $quantity, $notes) {
        try {
            $currentTime = date('Y-m-d H:i:s');
            
            $stmt = $this->db->prepare('
                INSERT INTO history (product_id, operation, quantity, notes, timestamp)
                VALUES (:product_id, :operation, :quantity, :notes, :timestamp)
            ');
            
            return $stmt->execute([
                ':product_id' => $productId,
                ':operation' => $operation,
                ':quantity' => $quantity,
                ':notes' => $notes,
                ':timestamp' => $currentTime
            ]);
        } catch (Exception $e) {
            error_log("Error en recordHistory: " . $e->getMessage());
            throw $e;
        }
    }

    public function getProductHistory($productId) {
        try {
            $stmt = $this->db->prepare('
                SELECT * FROM history 
                WHERE product_id = :product_id 
                ORDER BY timestamp DESC
            ');
            
            $stmt->execute([':product_id' => $productId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error en getProductHistory: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Agrega un nuevo producto al inventario
     *
     * @param string $name Nombre del producto
     * @param string $description Descripción del producto
     * @param int $stock Stock inicial
     * @param string $barcode Código de barras (opcional)
     * @return int ID del producto creado
     */
    public function addProduct($name, $description, $stock, $barcode = null) {
        try {
            $this->db->beginTransaction();
            
            $currentTime = date('Y-m-d H:i:s');
            
            // Verificar si el código de barras ya existe
            if ($barcode) {
                $existingProduct = $this->getProductByBarcode($barcode);
                if ($existingProduct) {
                    throw new Exception("El código de barras ya está asignado a otro producto");
                }
            }
            
            // Insertar el producto
            $stmt = $this->db->prepare("
                INSERT INTO products (name, description, stock, barcode, created_at, updated_at) 
                VALUES (:name, :description, :stock, :barcode, :created_at, :updated_at)
            ");
            
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':stock', $stock, PDO::PARAM_INT);
            $stmt->bindParam(':barcode', $barcode);
            $stmt->bindParam(':created_at', $currentTime);
            $stmt->bindParam(':updated_at', $currentTime);
            $stmt->execute();
            
            $productId = $this->db->lastInsertId();
            
            // Registrar en el historial si hay stock inicial
            if ($stock > 0) {
                $this->recordHistory($productId, 'add', $stock, 'Stock inicial');
            }
            
            $this->db->commit();
            return $productId;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en addProduct: " . $e->getMessage());
            throw new Exception('Error al agregar producto: ' . $e->getMessage());
        }
    }
    
    /**
     * Elimina un producto del inventario
     * @param int $productId ID del producto a eliminar
     * @return bool True si se eliminó correctamente
     */
    public function deleteProduct($productId) {
        try {
            $this->db->beginTransaction();
            
            // Obtener el producto
            $product = $this->getProduct($productId);
            if (!$product) {
                throw new Exception("Producto no encontrado");
            }
            
            // Registrar en el historial
            $this->recordHistory($productId, 'delete', $product['stock'], 'Producto eliminado');
            
            // Eliminar el producto
            $stmt = $this->db->prepare('DELETE FROM products WHERE id = :id');
            $stmt->execute([':id' => $productId]);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en deleteProduct: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Procesa un lote completo de productos
     * @param array $batchItems Array con los items del lote
     * @param string $notes Notas para el registro
     * @return array Detalles del procesamiento
     */
    public function processBatch($batchItems, $notes = 'Ingreso por lote') {
        try {
            $this->db->beginTransaction();
            
            $processed = [];
            $notFound = [];
            $errors = [];
            
            // Crear registro del lote
            $batchId = $this->createBatchRecord($notes);
            
            foreach ($batchItems as $item) {
                try {
                    if (!isset($item['barcode']) || !isset($item['quantity'])) {
                        throw new Exception("Datos incompletos en elemento del lote");
                    }
                    
                    $barcode = $item['barcode'];
                    $quantity = (int)$item['quantity'];
                    
                    if ($quantity <= 0) {
                        throw new Exception("La cantidad debe ser mayor a cero");
                    }
                    
                    // Buscar producto por código de barras
                    $product = $this->getProductByBarcode($barcode);
                    
                    if (!$product) {
                        $notFound[] = [
                            'barcode' => $barcode,
                            'quantity' => $quantity
                        ];
                        continue;
                    }
                    
                    // Actualizar stock
                    $this->updateStock(
                        $product['id'],
                        $quantity,
                        'add',
                        $notes . ' (Lote #' . $batchId . ')'
                    );
                    
                    // Registrar en la tabla batch_items
                    $this->registerBatchItem($batchId, $product['id'], $barcode, $quantity);
                    
                    $processed[] = [
                        'product_id' => $product['id'],
                        'product_name' => $product['name'],
                        'barcode' => $barcode,
                        'quantity' => $quantity
                    ];
                    
                } catch (Exception $e) {
                    // Compatibilidad PHP 5.6 - No usar el operador de fusión nula ??
                    $errorBarcode = isset($barcode) ? $barcode : 'desconocido';
                    $errors[] = [
                        'barcode' => $errorBarcode,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            // Si hay errores, revertir la transacción
            if (!empty($errors)) {
                $this->db->rollBack();
                throw new Exception("Se encontraron errores al procesar el lote");
            }
            
            // Si se procesaron elementos correctamente, confirmar la transacción
            if (!empty($processed)) {
                $this->db->commit();
            } else {
                $this->db->rollBack();
                throw new Exception("No se procesó ningún elemento del lote");
            }
            
            return [
                'processed' => $processed,
                'not_found' => $notFound,
                'total_processed' => count($processed),
                'total_not_found' => count($notFound),
                'batch_id' => $batchId
            ];
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en processBatch: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Crear un registro de lote
     * @param string $notes Notas del lote
     * @return int ID del lote creado
     */
    private function createBatchRecord($notes) {
        try {
            $currentTime = date('Y-m-d H:i:s');
            $user = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            
            $stmt = $this->db->prepare('
                INSERT INTO batches (notes, user_id, timestamp)
                VALUES (:notes, :user_id, :timestamp)
            ');
            
            $stmt->execute([
                ':notes' => $notes,
                ':user_id' => $user,
                ':timestamp' => $currentTime
            ]);
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Error en createBatchRecord: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Registrar un item en un lote
     * @param int $batchId ID del lote
     * @param int $productId ID del producto
     * @param string $barcode Código de barras
     * @param int $quantity Cantidad
     * @return bool True si se registró correctamente
     */
    private function registerBatchItem($batchId, $productId, $barcode, $quantity) {
        try {
            $stmt = $this->db->prepare('
                INSERT INTO batch_items (batch_id, product_id, barcode, quantity)
                VALUES (:batch_id, :product_id, :barcode, :quantity)
            ');
            
            return $stmt->execute([
                ':batch_id' => $batchId,
                ':product_id' => $productId,
                ':barcode' => $barcode,
                ':quantity' => $quantity
            ]);
        } catch (Exception $e) {
            error_log("Error en registerBatchItem: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtener el historial de lotes
     * @return array Lista de lotes con sus items
     */
    public function getBatchHistory() {
        try {
            // Adaptado para la tabla usuarios en lugar de users
            $stmt = $this->db->query('
                SELECT b.*, u.user as username
                FROM batches b
                LEFT JOIN usuarios u ON b.user_id = u.id
                ORDER BY b.timestamp DESC
            ');
            
            $batches = $stmt->fetchAll();
            $batchHistory = [];
            
            foreach ($batches as $batch) {
                // Obtener los items del lote
                $stmt = $this->db->prepare('
                    SELECT bi.*, p.name as product_name, p.description
                    FROM batch_items bi
                    JOIN products p ON bi.product_id = p.id
                    WHERE bi.batch_id = :batch_id
                ');
                
                $stmt->execute([':batch_id' => $batch['id']]);
                $items = $stmt->fetchAll();
                
                $batchHistory[] = [
                    'batch_id' => $batch['id'],
                    'notes' => $batch['notes'],
                    'user' => $batch['username'],
                    'timestamp' => $batch['timestamp'],
                    'items' => $items
                ];
            }
            
            return $batchHistory;
        } catch (Exception $e) {
            error_log("Error en getBatchHistory: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Devuelve la conexión a la base de datos
     * @return PDO La conexión a la base de datos
     */
    public function getDatabase() {
        return $this->db;
    }

    /**
     * Procesa un lote completo de productos para BAJA de inventario
     * @param array $batchItems Array con los items del lote
     * @param string $notes Notas para el registro
     * @return array Detalles del procesamiento
     */
    public function processBatchOutput($batchItems, $notes = 'Salida por lote') {
        try {
            $this->db->beginTransaction();
            
            $processed = [];
            $notFound = [];
            $insufficientStock = [];
            $errors = [];
            
            // Crear registro del lote de salida
            $batchId = $this->createBatchRecord($notes);
            
            foreach ($batchItems as $item) {
                try {
                    if (!isset($item['barcode']) || !isset($item['quantity'])) {
                        throw new Exception("Datos incompletos en elemento del lote");
                    }
                    
                    $barcode = $item['barcode'];
                    $quantity = (int)$item['quantity'];
                    
                    if ($quantity <= 0) {
                        throw new Exception("La cantidad debe ser mayor a cero");
                    }
                    
                    // Buscar producto por código de barras
                    $product = $this->getProductByBarcode($barcode);
                    
                    if (!$product) {
                        $notFound[] = [
                            'barcode' => $barcode,
                            'quantity' => $quantity
                        ];
                        continue;
                    }
                    
                    // Verificar si hay suficiente stock
                    if ($product['stock'] < $quantity) {
                        $insufficientStock[] = [
                            'product_id' => $product['id'],
                            'product_name' => $product['name'],
                            'barcode' => $barcode,
                            'requested' => $quantity,
                            'available' => $product['stock']
                        ];
                        continue;
                    }
                    
                    // Actualizar stock (restar)
                    $this->updateStock(
                        $product['id'],
                        $quantity,
                        'Salida', // Operación de salida
                        $notes . ' (Lote #' . $batchId . ')'
                    );
                    
                    // Registrar en la tabla batch_items con cantidad negativa para identificar salidas
                    $this->registerBatchItem($batchId, $product['id'], $barcode, -$quantity);
                    
                    $processed[] = [
                        'product_id' => $product['id'],
                        'product_name' => $product['name'],
                        'barcode' => $barcode,
                        'quantity' => $quantity,
                        'remaining_stock' => $product['stock'] - $quantity
                    ];
                    
                } catch (Exception $e) {
                    $errorBarcode = isset($barcode) ? $barcode : 'desconocido';
                    $errors[] = [
                        'barcode' => $errorBarcode,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            // Si hay stock insuficiente o errores, revertir la transacción
            if (!empty($errors) || !empty($insufficientStock)) {
                $this->db->rollBack();
                
                $errorMessage = "Se encontraron problemas al procesar el lote de salida";
                if (!empty($insufficientStock)) {
                    $errorMessage .= ". Stock insuficiente en algunos productos.";
                }
                if (!empty($errors)) {
                    $errorMessage .= ". Errores en procesamiento.";
                }
                
                return [
                    'success' => false,
                    'processed' => $processed,
                    'not_found' => $notFound,
                    'insufficient_stock' => $insufficientStock,
                    'errors' => $errors,
                    'total_processed' => count($processed),
                    'total_not_found' => count($notFound),
                    'total_insufficient' => count($insufficientStock),
                    'message' => $errorMessage
                ];
            }
            
            // Si se procesaron elementos correctamente, confirmar la transacción
            if (!empty($processed)) {
                $this->db->commit();
                return [
                    'success' => true,
                    'processed' => $processed,
                    'not_found' => $notFound,
                    'insufficient_stock' => $insufficientStock,
                    'total_processed' => count($processed),
                    'total_not_found' => count($notFound),
                    'total_insufficient' => count($insufficientStock),
                    'batch_id' => $batchId,
                    'message' => 'Lote de salida procesado exitosamente'
                ];
            } else {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'processed' => [],
                    'not_found' => $notFound,
                    'insufficient_stock' => $insufficientStock,
                    'total_processed' => 0,
                    'total_not_found' => count($notFound),
                    'total_insufficient' => count($insufficientStock),
                    'message' => 'No se procesó ningún elemento del lote'
                ];
            }
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en processBatchOutput: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Método específico para entradas de inventario
     * @param int $productId ID del producto
     * @param int $quantity Cantidad a agregar
     * @param string $notes Notas del movimiento
     * @return bool True si se actualizó correctamente
     */
    public function addStock($productId, $quantity, $notes = 'Entrada de inventario') {
        return $this->updateStock($productId, $quantity, 'add', $notes);
    }

    /**
     * Método específico para salidas de inventario
     * @param int $productId ID del producto
     * @param int $quantity Cantidad a retirar
     * @param string $notes Notas del movimiento
     * @return bool True si se actualizó correctamente
     */
    public function removeStock($productId, $quantity, $notes = 'Salida de inventario') {
        return $this->updateStock($productId, $quantity, 'Salida', $notes);
    }

    /**
     * Verifica si hay suficiente stock para una operación de salida
     * @param int $productId ID del producto
     * @param int $quantity Cantidad requerida
     * @return array Información sobre disponibilidad
     */
    public function checkStockAvailability($productId, $quantity) {
        try {
            $product = $this->getProduct($productId);
            if (!$product) {
                return [
                    'available' => false,
                    'reason' => 'Producto no encontrado',
                    'current_stock' => 0,
                    'requested' => $quantity
                ];
            }
            
            $isAvailable = $product['stock'] >= $quantity;
            
            return [
                'available' => $isAvailable,
                'reason' => $isAvailable ? 'Stock suficiente' : 'Stock insuficiente',
                'current_stock' => (int)$product['stock'],
                'requested' => (int)$quantity,
                'remaining_after' => $isAvailable ? ($product['stock'] - $quantity) : null
            ];
            
        } catch (Exception $e) {
            error_log("Error en checkStockAvailability: " . $e->getMessage());
            return [
                'available' => false,
                'reason' => 'Error al verificar stock: ' . $e->getMessage(),
                'current_stock' => 0,
                'requested' => $quantity
            ];
        }
    }

}