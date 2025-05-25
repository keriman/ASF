<?php
session_name('inventario_session');
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);


try {
    $db = new SQLite3('../assets/db/inventory.db');
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        // Preparar la consulta para evitar inyección SQL
        $stmt = $db->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $result = $stmt->execute();
        $user = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Login exitoso
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Redirigir al dashboard
            header("Location: ../index.php");
            exit();
        } else {
            // Login fallido
            $_SESSION['error'] = "Usuario o contraseña incorrectos";
            header("Location: ../pages/login.php");
            exit();
        }
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error de conexión a la base de datos";
    header("Location: ../pages/login.php");
    exit();
}
?>