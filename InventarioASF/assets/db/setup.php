<?php
try {
    // Crear o conectar a la base de datos SQLite
    $db = new SQLite3('inventory.db');
    
    // Crear tabla de usuarios
    $query = "CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                role TEXT NOT NULL CHECK(role IN ('admin', 'user')),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP)";
    
    $db->exec($query);
    
    // Insertar un usuario de prueba
    // Nota: En producción, nunca almacenes contraseñas en texto plano
    $username = 'admin';
    $password = password_hash('password123', PASSWORD_DEFAULT);
    
    $stmt = $db->prepare('INSERT OR IGNORE INTO users (username, password) VALUES (:username, :password)');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':password', $password, SQLITE3_TEXT);
    $stmt->execute();
    
    echo "Base de datos creada exitosamente y usuario de prueba insertado.";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>